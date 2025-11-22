<?php
$titulo = "Editar Aula";
$nivel = 1;
include '../includes/header.php';

if (!isProprietario()) {
    definirMensagem('error', 'Acesso negado! Apenas proprietários podem editar aulas.');
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID da aula não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Buscar dados da aula
$sql = "SELECT * FROM aulas WHERE id_aula = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    definirMensagem('error', 'Aula não encontrada');
    header('Location: index.php');
    exit;
}

$aula = $result->fetch_assoc();
$stmt->close();

// Buscar horários da aula
$sql = "SELECT * FROM aula_horario WHERE id_aula = ? ORDER BY dia_semana, hora_inicio";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$horarios_existentes = [];
while ($h = $result->fetch_assoc()) {
    $horarios_existentes[] = $h;
}
$stmt->close();

// Buscar modalidades e professores ativos
$modalidades = $conn->query("SELECT * FROM modalidades WHERE ativo = 1 ORDER BY nome");
$professores = $conn->query("SELECT * FROM professores WHERE ativo = 1 ORDER BY nome_professor");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modalidade_id = (int)$_POST['modalidade_id'];
    $professor_id = (int)$_POST['professor_id'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    $dias_semana = isset($_POST['dia_semana']) ? $_POST['dia_semana'] : [];
    $horas_inicio = isset($_POST['hora_inicio']) ? $_POST['hora_inicio'] : [];
    $horas_fim = isset($_POST['hora_fim']) ? $_POST['hora_fim'] : [];
    
    $erros = [];
    
    if ($modalidade_id <= 0) {
        $erros[] = "Selecione uma modalidade válida";
    }
    
    if ($professor_id <= 0) {
        $erros[] = "Selecione um professor válido";
    }
    
    if (empty($dias_semana)) {
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
    
    $horarios_validos = [];
    for ($i = 0; $i < count($dias_semana); $i++) {
        if (empty($dias_semana[$i]) || empty($horas_inicio[$i]) || empty($horas_fim[$i])) {
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
    
    // RF11: Validar conflito (exceto com a própria aula)
    if (empty($erros)) {
        foreach ($horarios_validos as $idx => $horario) {
            $sql = "SELECT a.id_aula, m.nome as modalidade
                    FROM aulas a
                    JOIN modalidades m ON a.modalidade_id = m.id_modalidade
                    JOIN aula_horario ah ON a.id_aula = ah.id_aula
                    WHERE a.professor_id = ? 
                    AND ah.dia_semana = ?
                    AND a.ativo = 1
                    AND a.id_aula != ?
                    AND (
                        (ah.hora_inicio < ? AND ah.hora_fim > ?) OR
                        (ah.hora_inicio < ? AND ah.hora_fim > ?) OR
                        (ah.hora_inicio >= ? AND ah.hora_fim <= ?)
                    )";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssssss", 
                $professor_id, 
                $horario['dia'],
                $id,
                $horario['fim'], $horario['inicio'],
                $horario['fim'], $horario['fim'],
                $horario['inicio'], $horario['fim']
            );
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $conflito = $result->fetch_assoc();
                $erros[] = "Horário " . ($idx + 1) . ": Conflito detectado";
            }
            $stmt->close();
        }
    }
    
    if (empty($erros)) {
        $conn->begin_transaction();
        
        try {
            // Atualizar aula
            $sql = "UPDATE aulas SET modalidade_id = ?, professor_id = ?, vagas_disponiveis = ?, ativo = ? WHERE id_aula = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiiii", $modalidade_id, $professor_id, $vagas_disponiveis, $ativo, $id);
            $stmt->execute();
            $stmt->close();
            
            // Remover horários antigos
            $sql = "DELETE FROM aula_horario WHERE id_aula = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            // Inserir novos horários
            $sql = "INSERT INTO aula_horario (id_aula, dia_semana, hora_inicio, hora_fim) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            foreach ($horarios_validos as $horario) {
                $stmt->bind_param("isss", $id, $horario['dia'], $horario['inicio'], $horario['fim']);
                $stmt->execute();
            }
            $stmt->close();
            
            $conn->commit();
            definirMensagem('success', 'Aula atualizada com sucesso!');
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $erros[] = "Erro ao atualizar aula: " . $e->getMessage();
        }
    }
    
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "<div class='mensagem mensagem-erro'>{$erro}</div>";
        }
    }
} else {
    $_POST = $aula;
}
?>

<div class="content-header">
    <h2>Editar Aula</h2>
</div>

<form method="POST" action="">
    <div class="form-row">
        <div class="form-group">
            <label for="modalidade_id">Modalidade *</label>
            <select id="modalidade_id" name="modalidade_id" required>
                <option value="">Selecione...</option>
                <?php while ($mod = $modalidades->fetch_assoc()): ?>
                    <option value="<?php echo $mod['id_modalidade']; ?>" 
                        data-duracao="<?php echo $mod['duracao_minutos']; ?>"
                        data-vagas="<?php echo $mod['vagas_maximas']; ?>"
                        <?php echo ($mod['id_modalidade'] == $_POST['modalidade_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($mod['nome']); ?> 
                        (<?php echo $mod['duracao_minutos']; ?> min - <?php echo $mod['vagas_maximas']; ?> vagas)
                    </option>
                <?php endwhile; ?>
            </select>
            <small style="color: var(--gray-text); display: block; margin-top: 5px;">
                💡 As vagas da aula serão atualizadas automaticamente pela modalidade
            </small>
        </div>
        
        <div class="form-group">
            <label for="professor_id">Professor *</label>
            <select id="professor_id" name="professor_id" required>
                <option value="">Selecione...</option>
                <?php while ($prof = $professores->fetch_assoc()): ?>
                    <option value="<?php echo $prof['id_professor']; ?>"
                        <?php echo ($prof['id_professor'] == $_POST['professor_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($prof['nome_professor']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
    
    <div class="section" style="background: #dbeafe; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="color: #1e40af; margin-bottom: 15px;">📅 Horários da Aula</h3>
        
        <div id="horarios-container">
            <?php foreach ($horarios_existentes as $idx => $horario): ?>
                <div class="horario-item" style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; position: relative;">
                    <?php if ($idx > 0): ?>
                        <button type="button" onclick="removerHorario(this)" 
                                style="position: absolute; top: 10px; right: 10px; background: #ef4444; color: white; border: none; border-radius: 4px; padding: 5px 10px; cursor: pointer;">
                            ❌ Remover
                        </button>
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Dia da Semana *</label>
                            <select name="dia_semana[]" required>
                                <option value="">Selecione...</option>
                                <?php
                                $dias = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
                                foreach ($dias as $dia): ?>
                                    <option value="<?php echo $dia; ?>" <?php echo ($horario['dia_semana'] == $dia) ? 'selected' : ''; ?>>
                                        <?php echo $dia; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Hora Início *</label>
                            <input type="time" name="hora_inicio[]" required value="<?php echo $horario['hora_inicio']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Hora Fim *</label>
                            <input type="time" name="hora_fim[]" required value="<?php echo $horario['hora_fim']; ?>">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <button type="button" class="btn btn-secondary btn-small" onclick="adicionarHorario()">
            ➕ Adicionar Outro Horário
        </button>
    </div>
    
    <div class="form-group">
        <label>
            <input type="checkbox" name="ativo" <?php echo $aula['ativo'] ? 'checked' : ''; ?>>
            Aula Ativa
        </label>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Salvar Alterações</button>
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
                <input type="time" name="hora_inicio[]" required>
            </div>
            
            <div class="form-group">
                <label>Hora Fim *</label>
                <input type="time" name="hora_fim[]" required>
            </div>
        </div>
    `;
    container.appendChild(novoHorario);
}

function removerHorario(btn) {
    btn.parentElement.remove();
}
</script>

<?php include '../includes/footer.php'; ?>