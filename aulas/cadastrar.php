<?php
$titulo = "Cadastrar Aula";
$nivel = 1;
include '../includes/header.php';

// Buscar modalidades e professores ativos
$modalidades = $conn->query("SELECT * FROM modalidades WHERE ativo = 1 ORDER BY nome");
$professores = $conn->query("SELECT * FROM professores WHERE ativo = 1 ORDER BY nome");

// Pré-selecionar professor se vier da URL
$professor_pre_selecionado = isset($_GET['professor_id']) ? (int)$_GET['professor_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modalidade_id = (int)$_POST['modalidade_id'];
    $professor_id = (int)$_POST['professor_id'];
    $dia_semana = sanitizarDados($conn, $_POST['dia_semana']);
    $hora_inicio = sanitizarDados($conn, $_POST['hora_inicio']);
    $hora_fim = sanitizarDados($conn, $_POST['hora_fim']);
    $vagas_disponiveis = (int)$_POST['vagas_disponiveis'];
    
    $erros = [];
    
    // Validações básicas
    if ($modalidade_id <= 0) {
        $erros[] = "Selecione uma modalidade válida";
    }
    
    if ($professor_id <= 0) {
        $erros[] = "Selecione um professor válido";
    }
    
    if (empty($dia_semana)) {
        $erros[] = "Selecione um dia da semana";
    } else {
        $erros[] = "Informe os horários de início e fim";
    }
    
    if ($hora_inicio >= $hora_fim) {
        $erros[] = "O horário de início deve ser anterior ao horário de fim";
    }
    
    if ($vagas_disponiveis <= 0) {
        $erros[] = "O número de vagas deve ser maior que zero";
    }
    
    // RF11: Validar se professor já tem aula no mesmo horário
    if (empty($erros)) {
        $sql = "SELECT a.id, m.nome as modalidade 
                FROM aulas a
                JOIN modalidades m ON a.modalidade_id = m.id
                WHERE a.professor_id = ? 
                AND a.dia_semana = ? 
                AND a.ativo = 1
                AND (
                    (a.hora_inicio < ? AND a.hora_fim > ?) OR
                    (a.hora_inicio < ? AND a.hora_fim > ?) OR
                    (a.hora_inicio >= ? AND a.hora_fim <= ?)
                )";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssss", 
            $professor_id, 
            $dia_semana, 
            $hora_fim, $hora_inicio,    // Conflito: aula existente termina depois que nova começa
            $hora_fim, $hora_fim,        // Conflito: aula existente começa antes que nova termina
            $hora_inicio, $hora_fim      // Conflito: aula nova contém aula existente
        );
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $conflito = $result->fetch_assoc();
            $erros[] = "RF11 VIOLADO: Este professor já possui uma aula cadastrada neste horário (" . $conflito['modalidade'] . " - " . $dia_semana . ")";
        }
        $stmt->close();
    }
    
    // Verificar se modalidade e professor estão vinculados
    if (empty($erros)) {
        $sql = "SELECT id FROM professor_modalidade WHERE professor_id = ? AND modalidade_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $professor_id, $modalidade_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $erros[] = "AVISO: Este professor não está vinculado a esta modalidade. Considere vincular em 'Editar Professor'.";
            // Não bloqueia, apenas avisa
        }
        $stmt->close();
    }
    
    // Inserir aula se não houver erros
    if (empty($erros)) {
        $sql = "INSERT INTO aulas (modalidade_id, professor_id, dia_semana, hora_inicio, hora_fim, vagas_disponiveis) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssi", $modalidade_id, $professor_id, $dia_semana, $hora_inicio, $hora_fim, $vagas_disponiveis);
        
        if ($stmt->execute()) {
            definirMensagem('success', 'Aula cadastrada com sucesso!');
            header('Location: index.php');
            exit;
        } else {
            $erros[] = "Erro ao cadastrar aula: " . $stmt->error;
        }
        $stmt->close();
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
    ℹ️ <strong>RF11:</strong> O sistema não permite cadastrar mais de uma aula no mesmo horário para o mesmo professor.
</div>

<form method="POST" action="">
    <div class="form-row">
        <div class="form-group">
            <label for="modalidade_id">Modalidade *</label>
            <select id="modalidade_id" name="modalidade_id" required>
                <option value="">Selecione...</option>
                <?php while ($mod = $modalidades->fetch_assoc()): ?>
                    <option value="<?php echo $mod['id']; ?>" 
                        data-duracao="<?php echo $mod['duracao_minutos']; ?>"
                        <?php echo (isset($_POST['modalidade_id']) && $_POST['modalidade_id'] == $mod['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($mod['nome']); ?> (<?php echo $mod['duracao_minutos']; ?> min)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="professor_id">Professor * (RF7)</label>
            <select id="professor_id" name="professor_id" required>
                <option value="">Selecione...</option>
                <?php while ($prof = $professores->fetch_assoc()): ?>
                    <option value="<?php echo $prof['id']; ?>"
                        <?php 
                        if (isset($_POST['professor_id']) && $_POST['professor_id'] == $prof['id']) {
                            echo 'selected';
                        } elseif ($professor_pre_selecionado == $prof['id']) {
                            echo 'selected';
                        }
                        ?>>
                        <?php echo htmlspecialchars($prof['nome']); ?>
                        <?php if ($prof['especialidade']): ?>
                            - <?php echo htmlspecialchars($prof['especialidade']); ?>
                        <?php endif; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="dia_semana">Dia da Semana *</label>
            <select id="dia_semana" name="dia_semana" required>
                <option value="">Selecione...</option>
                <?php
                $dias = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
                foreach ($dias as $dia):
                ?>
                    <option value="<?php echo $dia; ?>" <?php echo (isset($_POST['dia_semana']) && $_POST['dia_semana'] == $dia) ? 'selected' : ''; ?>>
                        <?php echo $dia; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="vagas_disponiveis">Vagas Disponíveis *</label>
            <input type="number" id="vagas_disponiveis" name="vagas_disponiveis" required min="1" 
                value="<?php echo isset($_POST['vagas_disponiveis']) ? $_POST['vagas_disponiveis'] : '15'; ?>">
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="hora_inicio">Horário de Início *</label>
            <input type="time" id="hora_inicio" name="hora_inicio" required 
                value="<?php echo isset($_POST['hora_inicio']) ? $_POST['hora_inicio'] : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="hora_fim">Horário de Término *</label>
            <input type="time" id="hora_fim" name="hora_fim" required 
                value="<?php echo isset($_POST['hora_fim']) ? $_POST['hora_fim'] : ''; ?>">
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Cadastrar</button>
        <a href="index.php" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<script>
// Calcular automaticamente hora_fim baseado na duração da modalidade
document.getElementById('modalidade_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const duracao = selectedOption.getAttribute('data-duracao');
    const horaInicio = document.getElementById('hora_inicio').value;
    
    if (duracao && horaInicio) {
        calcularHoraFim(horaInicio, parseInt(duracao));
    }
});

document.getElementById('hora_inicio').addEventListener('change', function() {
    const modalidadeSelect = document.getElementById('modalidade_id');
    const selectedOption = modalidadeSelect.options[modalidadeSelect.selectedIndex];
    const duracao = selectedOption.getAttribute('data-duracao');
    
    if (duracao && this.value) {
        calcularHoraFim(this.value, parseInt(duracao));
    }
});

function calcularHoraFim(horaInicio, duracaoMinutos) {
    const [horas, minutos] = horaInicio.split(':').map(Number);
    const inicioEmMinutos = horas * 60 + minutos;
    const fimEmMinutos = inicioEmMinutos + duracaoMinutos;
    
    const horasFim = Math.floor(fimEmMinutos / 60);
    const minutosFim = fimEmMinutos % 60;
    
    const horaFimFormatada = String(horasFim).padStart(2, '0') + ':' + String(minutosFim).padStart(2, '0');
    document.getElementById('hora_fim').value = horaFimFormatada;
}
</script>

<?php include '../includes/footer.php'; ?>