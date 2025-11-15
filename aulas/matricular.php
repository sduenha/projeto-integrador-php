<?php
$titulo = "Matricular Aluno";
$nivel = 1;
include '../includes/header.php';

// Verificar parâmetros
$aula_id = isset($_GET['aula_id']) ? (int)$_GET['aula_id'] : null;
$aluno_id = isset($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : null;

// Buscar informações da aula se foi passada
$aula_info = null;
if ($aula_id) {
    $sql = "SELECT a.*, m.nome as modalidade_nome, p.nome as professor_nome,
            (SELECT COUNT(*) FROM matriculas WHERE aula_id = a.id AND ativo = 1) as total_matriculados
            FROM aulas a
            JOIN modalidades m ON a.modalidade_id = m.id
            JOIN professores p ON a.professor_id = p.id
            WHERE a.id = ? AND a.ativo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $aula_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $aula_info = $result->fetch_assoc();
    }
    $stmt->close();
}

// Buscar todas as aulas disponíveis
$aulas = $conn->query("
    SELECT a.*, m.nome as modalidade_nome, p.nome as professor_nome,
    (SELECT COUNT(*) FROM matriculas WHERE aula_id = a.id AND ativo = 1) as total_matriculados
    FROM aulas a
    JOIN modalidades m ON a.modalidade_id = m.id
    JOIN professores p ON a.professor_id = p.id
    WHERE a.ativo = 1
    ORDER BY FIELD(a.dia_semana, 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'), a.hora_inicio
");

// Buscar todos os alunos ativos
$alunos = $conn->query("SELECT * FROM alunos WHERE ativo = 1 ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aluno_id_post = (int)$_POST['aluno_id'];
    $aula_id_post = (int)$_POST['aula_id'];
    
    $erros = [];
    
    // Validações básicas
    if ($aluno_id_post <= 0) {
        $erros[] = "Selecione um aluno válido";
    }
    
    if ($aula_id_post <= 0) {
        $erros[] = "Selecione uma aula válida";
    }
    
    // Verificar se já está matriculado
    if (empty($erros)) {
        $sql = "SELECT id FROM matriculas WHERE aluno_id = ? AND aula_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $aluno_id_post, $aula_id_post);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $erros[] = "Este aluno já está matriculado nesta aula";
        }
        $stmt->close();
    }
    
    // RF12: Verificar conflito de horário para o aluno
    if (empty($erros)) {
        $sql = "SELECT a.dia_semana, a.hora_inicio, a.hora_fim, m.nome as modalidade
                FROM matriculas mat
                JOIN aulas a ON mat.aula_id = a.id
                JOIN modalidades m ON a.modalidade_id = m.id
                WHERE mat.aluno_id = ?
                AND mat.ativo = 1
                AND a.ativo = 1
                AND a.dia_semana = (SELECT dia_semana FROM aulas WHERE id = ?)
                AND (
                    (a.hora_inicio < (SELECT hora_fim FROM aulas WHERE id = ?) 
                     AND a.hora_fim > (SELECT hora_inicio FROM aulas WHERE id = ?)) OR
                    (a.hora_inicio < (SELECT hora_fim FROM aulas WHERE id = ?) 
                     AND a.hora_fim > (SELECT hora_fim FROM aulas WHERE id = ?)) OR
                    (a.hora_inicio >= (SELECT hora_inicio FROM aulas WHERE id = ?) 
                     AND a.hora_fim <= (SELECT hora_fim FROM aulas WHERE id = ?))
                )";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiiiiii", 
            $aluno_id_post, 
            $aula_id_post,
            $aula_id_post, $aula_id_post,
            $aula_id_post, $aula_id_post,
            $aula_id_post, $aula_id_post
        );
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $conflito = $result->fetch_assoc();
            $erros[] = "RF12 VIOLADO: Este aluno já possui outra aula cadastrada neste horário (" . 
                      $conflito['modalidade'] . " - " . $conflito['dia_semana'] . " às " . 
                      date('H:i', strtotime($conflito['hora_inicio'])) . ")";
        }
        $stmt->close();
    }
    
    // Verificar se há vagas disponíveis
    if (empty($erros)) {
        $sql = "SELECT a.vagas_disponiveis,
                (SELECT COUNT(*) FROM matriculas WHERE aula_id = a.id AND ativo = 1) as total_matriculados
                FROM aulas a
                WHERE a.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $aula_id_post);
        $stmt->execute();
        $result = $stmt->get_result();
        $aula_check = $result->fetch_assoc();
        
        if ($aula_check['total_matriculados'] >= $aula_check['vagas_disponiveis']) {
            $erros[] = "Esta aula não possui mais vagas disponíveis";
        }
        $stmt->close();
    }
    
    // Realizar matrícula
    if (empty($erros)) {
        $sql = "INSERT INTO matriculas (aluno_id, aula_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $aluno_id_post, $aula_id_post);
        
        if ($stmt->execute()) {
            definirMensagem('success', 'Aluno matriculado com sucesso!');
            header('Location: matricular.php?aula_id=' . $aula_id_post);
            exit;
        } else {
            $erros[] = "Erro ao matricular aluno: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "<div class='mensagem mensagem-erro'>{$erro}</div>";
        }
    }
}

// Buscar alunos já matriculados na aula (se aula foi selecionada)
$matriculados = null;
if ($aula_id) {
    $sql = "SELECT m.*, a.nome as aluno_nome, a.email as aluno_email, a.telefone as aluno_telefone
            FROM matriculas m
            JOIN alunos a ON m.aluno_id = a.id
            WHERE m.aula_id = ? AND m.ativo = 1
            ORDER BY a.nome";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $aula_id);
    $stmt->execute();
    $matriculados = $stmt->get_result();
    $stmt->close();
}
?>

<div class="content-header">
    <h2>Matricular Aluno em Aula (RF4, RF6)</h2>
    <a href="index.php" class="btn btn-secondary">↩️ Voltar</a>
</div>

<div class="mensagem mensagem-info">
    ℹ️ <strong>RF12:</strong> O sistema não permite matricular um aluno em mais de uma aula no mesmo horário.
</div>

<?php if ($aula_info): ?>
    <div class="section">
        <h3 class="section-title">Informações da Aula Selecionada</h3>
        <div class="info-list">
            <div class="info-item">
                <span class="info-label">Modalidade:</span>
                <span class="info-value"><strong><?php echo htmlspecialchars($aula_info['modalidade_nome']); ?></strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Professor:</span>
                <span class="info-value"><?php echo htmlspecialchars($aula_info['professor_nome']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Dia e Horário:</span>
                <span class="info-value"><?php echo $aula_info['dia_semana'] . ' - ' . date('H:i', strtotime($aula_info['hora_inicio'])) . ' às ' . date('H:i', strtotime($aula_info['hora_fim'])); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Vagas:</span>
                <span class="info-value">
                    <strong><?php echo $aula_info['total_matriculados']; ?></strong> / <?php echo $aula_info['vagas_disponiveis']; ?>
                    <?php if ($aula_info['total_matriculados'] >= $aula_info['vagas_disponiveis']): ?>
                        <span class="badge badge-danger">LOTADA</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="section">
    <h3 class="section-title">Nova Matrícula</h3>
    
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label for="aula_id">Aula *</label>
                <select id="aula_id" name="aula_id" required onchange="window.location.href='matricular.php?aula_id=' + this.value">
                    <option value="">Selecione uma aula...</option>
                    <?php while ($aula = $aulas->fetch_assoc()): ?>
                        <option value="<?php echo $aula['id']; ?>"
                            <?php echo ($aula_id == $aula['id']) ? 'selected' : ''; ?>
                            <?php echo ($aula['total_matriculados'] >= $aula['vagas_disponiveis']) ? 'disabled' : ''; ?>>
                            <?php echo htmlspecialchars($aula['modalidade_nome']); ?> - 
                            <?php echo htmlspecialchars($aula['professor_nome']); ?> - 
                            <?php echo $aula['dia_semana'] . ' ' . date('H:i', strtotime($aula['hora_inicio'])); ?>
                            (<?php echo $aula['total_matriculados']; ?>/<?php echo $aula['vagas_disponiveis']; ?> vagas)
                            <?php echo ($aula['total_matriculados'] >= $aula['vagas_disponiveis']) ? ' - LOTADA' : ''; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="aluno_id">Aluno *</label>
                <select id="aluno_id" name="aluno_id" required>
                    <option value="">Selecione um aluno...</option>
                    <?php while ($aluno = $alunos->fetch_assoc()): ?>
                        <option value="<?php echo $aluno['id']; ?>"
                            <?php echo ($aluno_id == $aluno['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($aluno['nome']); ?> - <?php echo htmlspecialchars($aluno['email']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">✅ Matricular Aluno</button>
        </div>
    </form>
</div>

<?php if ($matriculados && $matriculados->num_rows > 0): ?>
    <div class="section">
        <h3 class="section-title">Alunos Matriculados nesta Aula</h3>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Data da Matrícula</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($mat = $matriculados->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($mat['aluno_nome']); ?></strong></td>
                            <td><?php echo htmlspecialchars($mat['aluno_email']); ?></td>
                            <td><?php echo htmlspecialchars($mat['aluno_telefone']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($mat['data_matricula'])); ?></td>
                            <td>
                                <a href="desmatricular.php?id=<?php echo $mat['id']; ?>&aula_id=<?php echo $aula_id; ?>" 
                                   class="btn btn-danger btn-small"
                                   onclick="return confirm('Tem certeza que deseja desmatricular este aluno?')">
                                   ❌ Desmatricular
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($aula_id): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📋</div>
        <p>Nenhum aluno matriculado nesta aula ainda</p>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>