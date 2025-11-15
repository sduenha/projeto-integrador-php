<?php
$titulo = "Editar Aula";
$nivel = 1;
include '../includes/header.php';

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID da aula não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Buscar dados da aula
$sql = "SELECT * FROM aulas WHERE id = ?";
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

// Buscar modalidades e professores ativos
$modalidades = $conn->query("SELECT * FROM modalidades WHERE ativo = 1 ORDER BY nome");
$professores = $conn->query("SELECT * FROM professores WHERE ativo = 1 ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modalidade_id = (int)$_POST['modalidade_id'];
    $professor_id = (int)$_POST['professor_id'];
    $dia_semana = sanitizarDados($conn, $_POST['dia_semana']);
    $hora_inicio = sanitizarDados($conn, $_POST['hora_inicio']);
    $hora_fim = sanitizarDados($conn, $_POST['hora_fim']);
    $vagas_disponiveis = (int)$_POST['vagas_disponiveis'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
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
    
    // RF11: Validar conflito de horário (exceto com a própria aula)
    if (empty($erros)) {
        $sql = "SELECT a.id, m.nome as modalidade 
                FROM aulas a
                JOIN modalidades m ON a.modalidade_id = m.id
                WHERE a.professor_id = ? 
                AND a.dia_semana = ? 
                AND a.ativo = 1
                AND a.id != ?
                AND (
                    (a.hora_inicio < ? AND a.hora_fim > ?) OR
                    (a.hora_inicio < ? AND a.hora_fim > ?) OR
                    (a.hora_inicio >= ? AND a.hora_fim <= ?)
                )";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isissssss", 
            $professor_id, 
            $dia_semana,
            $id,  // Excluir a própria aula da verificação
            $hora_fim, $hora_inicio,
            $hora_fim, $hora_fim,
            $hora_inicio, $hora_fim
        );
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $conflito = $result->fetch_assoc();
            $erros[] = "RF11 VIOLADO: Este professor já possui outra aula cadastrada neste horário (" . $conflito['modalidade'] . " - " . $dia_semana . ")";
        }
        $stmt->close();
    }
    
    // Atualizar aula
    if (empty($erros)) {
        $sql = "UPDATE aulas SET modalidade_id = ?, professor_id = ?, dia_semana = ?, 
                hora_inicio = ?, hora_fim = ?, vagas_disponiveis = ?, ativo = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssiii", $modalidade_id, $professor_id, $dia_semana, 
                         $hora_inicio, $hora_fim, $vagas_disponiveis, $ativo, $id);
        
        if ($stmt->execute()) {
            definirMensagem('success', 'Aula atualizada com sucesso!');
            header('Location: index.php');
            exit;
        } else {
            $erros[] = "Erro ao atualizar aula: " . $stmt->error;
        }
        $stmt->close();
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
                    <option value="<?php echo $mod['id']; ?>" 
                        data-duracao="<?php echo $mod['duracao_minutos']; ?>"
                        <?php echo ($mod['id'] == $_POST['modalidade_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($mod['nome']); ?> (<?php echo $mod['duracao_minutos']; ?> min)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="professor_id">Professor *</label>
            <select id="professor_id" name="professor_id" required>
                <option value="">Selecione...</option>
                <?php while ($prof = $professores->fetch_assoc()): ?>
                    <option value="<?php echo $prof['id']; ?>"
                        <?php echo ($prof['id'] == $_POST['professor_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($prof['nome']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="dia_semana">Dia da Semana *</label>
            <select id="dia_semana" name="dia_semana" required>
                <?php
                $dias = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
                foreach ($dias as $dia):
                ?>
                    <option value="<?php echo $dia; ?>" <?php echo ($_POST['dia_semana'] == $dia) ? 'selected' : ''; ?>>
                        <?php echo $dia; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="vagas_disponiveis">Vagas Disponíveis *</label>
            <input type="number" id="vagas_disponiveis" name="vagas_disponiveis" required min="1" 
                value="<?php echo $_POST['vagas_disponiveis']; ?>">
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="hora_inicio">Horário de Início *</label>
            <input type="time" id="hora_inicio" name="hora_inicio" required 
                value="<?php echo $_POST['hora_inicio']; ?>">
        </div>
        
        <div class="form-group">
            <label for="hora_fim">Horário de Término *</label>
            <input type="time" id="hora_fim" name="hora_fim" required 
                value="<?php echo $_POST['hora_fim']; ?>">
        </div>
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

<?php include '../includes/footer.php'; ?>