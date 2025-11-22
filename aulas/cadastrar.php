<?php
$titulo = "Cadastrar Aula";
$nivel = 1;
include '../includes/header.php';

if (!isProprietario()) {
    definirMensagem('error', 'Acesso negado! Apenas proprietários podem cadastrar aulas.');
    header('Location: index.php');
    exit;
}

// Buscar modalidades e professores ativos
$modalidades = $conn->query("SELECT * FROM modalidades WHERE ativo = 1 ORDER BY nome");
$professores = $conn->query("SELECT * FROM professores WHERE ativo = 1 ORDER BY nome_professor");

$professor_pre_selecionado = isset($_GET['professor_id']) ? (int)$_GET['professor_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modalidade_id = (int)$_POST['modalidade_id'];
    $professor_id = (int)$_POST['professor_id'];
    
    // Horários (podem ser múltiplos)
    $dias_semana = isset($_POST['dia_semana']) ? $_POST['dia_semana'] : [];
    $horas_inicio = isset($_POST['hora_inicio']) ? $_POST['hora_inicio'] : [];
    $horas_fim = isset($_POST['hora_fim']) ? $_POST['hora_fim'] : [];
    
    $erros = [];
    
    // Validações básicas
    if ($modalidade_id <= 0) {
        $erros[] = "Selecione uma modalidade válida";
    }
    
    if ($professor_id <= 0) {
        $erros[] = "Selecione um professor válido";
    }
    
    if (empty($dias_semana) || empty($horas_inicio) || empty($horas_fim)) {
        $erros[] = "Adicione pelo menos um horário";
    }
    
    // Buscar vagas_maximas da modalidade selecionada
    $vagas_disponiveis = 0;
    if ($modalidade_id > 0) {
        $sql = "SELECT vagas_maximas FROM modalidades WHERE id_modalidade = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $modalidade_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $vagas_disponiveis = $result->fetch_assoc()['vagas_maximas'];
        } else {
            $erros[] = "Modalidade selecionada não encontrada";
        }
        $stmt->close();
    }
    
    // Validar cada horário
    $horarios_validos = [];
    for ($i = 0; $i < count($dias_semana); $i++) {
        if (empty($dias_semana[$i]) || empty($horas_inicio[$i]) || empty($horas_fim[$i])) {
            $erros[] = "Horário " . ($i + 1) . " incompleto";
            continue;
        }
        
        if ($horas_inicio[$i] >= $horas_fim[$i]) {
            $erros[] = "Horário " . ($i + 1) . ": início deve ser antes do fim";
            continue;
        }
        
        $horarios_validos[] = [
            'dia' => sanitizarDados($conn, $dias_semana[$i]),
            'inicio' => sanitizarDados($conn, $horas_inicio[$i]),
            'fim' => sanitizarDados($conn, $horas_fim[$i])
        ];
    }
    
    // RF11: Validar conflito de horário para professor
    if (empty($erros)) {
        foreach ($horarios_validos as $idx => $horario) {
            $sql = "SELECT a.id_aula, m.nome as modalidade, ah.dia_semana, ah.hora_inicio, ah.hora_fim
                    FROM aulas a
                    JOIN modalidades m ON a.modalidade_id = m.id_modalidade
                    JOIN aula_horario ah ON a.id_aula = ah.id_aula
                    WHERE a.professor_id = ? 
                    AND ah.dia_semana = ?
                    AND a.ativo = 1
                    AND (
                        (ah.hora_inicio < ? AND ah.hora_fim > ?) OR
                        (ah.hora_inicio < ? AND ah.hora_fim > ?) OR
                        (ah.hora_inicio >= ? AND ah.hora_fim <= ?)
                    )";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssss", 
                $professor_id, 
                $horario['dia'],
                $horario['fim'], $horario['inicio'],
                $horario['fim'], $horario['fim'],
                $horario['inicio'], $horario['fim']
            );
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $conflito = $result->fetch_assoc();
                $erros[] = "Horário " . ($idx + 1) . ": Professor já possui aula em " . $horario['dia'] . " neste horário (" . $conflito['modalidade'] . ")";
            }
            $stmt->close();
        }
    }
    
    // Inserir aula se não houver erros
    if (empty($erros)) {
        $conn->begin_transaction();
        
        try {
            // 1. Inserir aula
            $sql = "INSERT INTO aulas (modalidade_id, professor_id, vagas_disponiveis) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $modalidade_id, $professor_id, $vagas_disponiveis);
            $stmt->execute();
            
            $aula_id = $conn->insert_id;
            $stmt->close();
            
            // 2. Inserir horários
            $sql = "INSERT INTO aula_horario (id_aula, dia_semana, hora_inicio, hora_fim) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            foreach ($horarios_validos as $horario) {
                $stmt->bind_param("isss", $aula_id, $horario['dia'], $horario['inicio'], $horario['fim']);
                $stmt->execute();
            }
            $stmt->close();
            
            $conn->commit();
            definirMensagem('success', 'Aula cadastrada com sucesso!');
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $erros[] = "Erro ao cadastrar aula: " . $e->getMessage();
        }
    }
    
    // Exibir erros
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            $classe = strpos($erro, 'AVISO') !== false ? 'mensagem-aviso' : 'mensagem-erro';
            echo "<div class='mensagem {$classe}'>{$erro}</div>";
        }
    }
}
?>

<div class="content-header">
    <h2>Cadastrar Nova Aula</h2>
</div>

<div class="mensagem mensagem-info">
    ℹ️ O sistema não permite cadastrar mais de uma aula no mesmo horário para o mesmo professor.
</div>

<form method="POST" action="" id="form-aula">
    <div class="form-row">
        <div class="form-group">
            <label for="modalidade_id">Modalidade *</label>
            <select id="modalidade_id" name="modalidade_id" required>
                <option value="">Selecione...</option>
                <?php while ($mod = $modalidades->fetch_assoc()): ?>
                    <option value="<?php echo $mod['id_modalidade']; ?>" 
                        data-duracao="<?php echo $mod['duracao_minutos']; ?>"
                        data-vagas="<?php echo $mod['vagas_maximas']; ?>"
                        <?php echo (isset($_POST['modalidade_id']) && $_POST['modalidade_id'] == $mod['id_modalidade']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($mod['nome']); ?> 
                        (<?php echo $mod['duracao_minutos']; ?> min - <?php echo $mod['vagas_maximas']; ?> vagas)
                    </option>
                <?php endwhile; ?>
            </select>
            <small style="color: var(--gray-text); display: block; margin-top: 5px;">
                💡 As vagas da aula serão definidas automaticamente pela modalidade
            </small>
        </div>
        
        <div class="form-group">
            <label for="professor_id">Professor</label>
            <select id="professor_id" name="professor_id" required>
                <option value="">Selecione...</option>
                <?php while ($prof = $professores->fetch_assoc()): ?>
                    <option value="<?php echo $prof['id_professor']; ?>"
                        <?php 
                        if (isset($_POST['professor_id']) && $_POST['professor_id'] == $prof['id_professor']) {
                            echo 'selected';
                        } elseif ($professor_pre_selecionado == $prof['id_professor']) {
                            echo 'selected';
                        }
                        ?>>
                        <?php echo htmlspecialchars($prof['nome_professor']); ?>
                        <?php if ($prof['especialidade']): ?>
                            - <?php echo htmlspecialchars($prof['especialidade']); ?>
                        <?php endif; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
    
    <div class="section" style="background: #dbeafe; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="color: #1e40af; margin-bottom: 15px;">📅 Horários da Aula</h3>
        <p style="color: #1e3a8a; margin-bottom: 15px;">Adicione um ou mais horários para esta aula:</p>
        
        <div id="horarios-container">
            <div class="horario-item" style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Dia da Semana *</label>
                        <select name="dia_semana[]" required>
                            <option value="">Selecione...</option>
                            <option value="Segunda">Segunda</option>
                            <option value="Terça">Terça</option>
                            <option value="Quarta">Quarta</option>
                            <option value="Quinta">Quinta</option>
                            <option value="Sexta">Sexta</option>
                            <option value="Sábado">Sábado</option>
                            <option value="Domingo">Domingo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Hora Início *</label>
                        <input type="time" name="hora_inicio[]" required class="hora-inicio">
                    </div>
                    
                    <div class="form-group">
                        <label>Hora Fim *</label>
                        <input type="time" name="hora_fim[]" required class="hora-fim">
                    </div>
                </div>
            </div>
        </div>
        
        <button type="button" class="btn btn-secondary btn-small" onclick="adicionarHorario()">
            ➕ Adicionar Outro Horário
        </button>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Cadastrar</button>
        <a href="index.php" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<script>
function adicionarHorario() {
    const container = document.getElementById('horarios-container');
    const novoHorario = document.createElement('div');
    novoHorario.className = 'horario-item';
    novoHorario.style.cssText = 'background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; position: relative;';
    novoHorario.innerHTML = `
        <button type="button" onclick="removerHorario(this)" 
                style="position: absolute; top: 10px; right: 10px; background: #ef4444; color: white; border: none; border-radius: 4px; padding: 5px 10px; cursor: pointer;">
            ❌ Remover
        </button>
        <div class="form-row">
            <div class="form-group">
                <label>Dia da Semana *</label>
                <select name="dia_semana[]" required>
                    <option value="">Selecione...</option>
                    <option value="Segunda">Segunda</option>
                    <option value="Terça">Terça</option>
                    <option value="Quarta">Quarta</option>
                    <option value="Quinta">Quinta</option>
                    <option value="Sexta">Sexta</option>
                    <option value="Sábado">Sábado</option>
                    <option value="Domingo">Domingo</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Hora Início *</label>
                <input type="time" name="hora_inicio[]" required class="hora-inicio">
            </div>
            
            <div class="form-group">
                <label>Hora Fim *</label>
                <input type="time" name="hora_fim[]" required class="hora-fim">
            </div>
        </div>
    `;
    container.appendChild(novoHorario);
    
    // Adicionar listener para calcular hora fim automaticamente
    const horaInicio = novoHorario.querySelector('.hora-inicio');
    horaInicio.addEventListener('change', function() {
        calcularHoraFim(this);
    });
}

function removerHorario(btn) {
    btn.parentElement.remove();
}

// Calcular hora fim baseado na duração da modalidade
document.getElementById('modalidade_id').addEventListener('change', function() {
    const horasInicio = document.querySelectorAll('.hora-inicio');
    horasInicio.forEach(input => {
        if (input.value) {
            calcularHoraFim(input);
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.hora-inicio').forEach(input => {
        input.addEventListener('change', function() {
            calcularHoraFim(this);
        });
    });
});

function calcularHoraFim(inputInicio) {
    const modalidadeSelect = document.getElementById('modalidade_id');
    const selectedOption = modalidadeSelect.options[modalidadeSelect.selectedIndex];
    const duracao = selectedOption.getAttribute('data-duracao');
    
    if (duracao && inputInicio.value) {
        const [horas, minutos] = inputInicio.value.split(':').map(Number);
        const inicioEmMinutos = horas * 60 + minutos;
        const fimEmMinutos = inicioEmMinutos + parseInt(duracao);
        
        const horasFim = Math.floor(fimEmMinutos / 60);
        const minutosFim = fimEmMinutos % 60;
        
        const horaFimFormatada = String(horasFim).padStart(2, '0') + ':' + String(minutosFim).padStart(2, '0');
        
        const inputFim = inputInicio.closest('.form-row').querySelector('.hora-fim');
        inputFim.value = horaFimFormatada;
    }
}
</script>

<?php include '../includes/footer.php'; ?>