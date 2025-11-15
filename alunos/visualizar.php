<?php
$titulo = "Visualizar Aluno";
$nivel = 1;
include '../includes/header.php';

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID do aluno não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Buscar dados do aluno
$sql = "SELECT * FROM alunos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    definirMensagem('error', 'Aluno não encontrado');
    header('Location: index.php');
    exit;
}

$aluno = $result->fetch_assoc();
$stmt->close();

// Buscar aulas matriculadas
$sql = "SELECT m.*, a.dia_semana, a.hora_inicio, a.hora_fim, 
        mo.nome as modalidade_nome, p.nome as professor_nome
        FROM matriculas m
        JOIN aulas a ON m.aula_id = a.id
        JOIN modalidades mo ON a.modalidade_id = mo.id
        JOIN professores p ON a.professor_id = p.id
        WHERE m.aluno_id = ? AND m.ativo = 1
        ORDER BY a.dia_semana, a.hora_inicio";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$matriculas = $stmt->get_result();
$stmt->close();
?>

<div class="content-header">
    <h2>Detalhes do Aluno</h2>
    <div>
        <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary">✏️ Editar</a>
        <a href="index.php" class="btn btn-secondary">↩️ Voltar</a>
    </div>
</div>

<div class="section">
    <h3 class="section-title">Informações Pessoais</h3>
    <div class="info-list">
        <div class="info-item">
            <span class="info-label">Nome:</span>
            <span class="info-value"><?php echo htmlspecialchars($aluno['nome']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Email:</span>
            <span class="info-value"><?php echo htmlspecialchars($aluno['email']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Telefone:</span>
            <span class="info-value"><?php echo htmlspecialchars($aluno['telefone']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Data de Nascimento:</span>
            <span class="info-value"><?php echo $aluno['data_nascimento'] ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '-'; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Endereço:</span>
            <span class="info-value"><?php echo htmlspecialchars($aluno['endereco']) ?: '-'; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Status:</span>
            <span class="info-value">
                <?php if ($aluno['ativo']): ?>
                    <span class="badge badge-success">Ativo</span>
                <?php else: ?>
                    <span class="badge badge-danger">Inativo</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Data de Cadastro:</span>
            <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($aluno['data_cadastro'])); ?></span>
        </div>
    </div>
</div>

<div class="section">
    <h3 class="section-title">🔐 Acesso ao Sistema</h3>
    
    <?php
    // Verificar se existe usuário vinculado
    $sql = "SELECT u.*, 
            CASE WHEN u.ultimo_acesso IS NOT NULL THEN 'Sim' ELSE 'Nunca' END as ja_acessou
            FROM usuarios u
            WHERE u.vinculo_id = ? AND u.tipo_usuario = 'aluno'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario_vinculado = $result->fetch_assoc();
    $stmt->close();
    ?>
    
    <?php if ($usuario_vinculado): ?>
        <div class="info-list">
            <div class="info-item">
                <span class="info-label">Status do Acesso:</span>
                <span class="info-value">
                    <?php if ($usuario_vinculado['ativo']): ?>
                        <span class="badge badge-success">✅ Ativo</span>
                    <?php else: ?>
                        <span class="badge badge-danger">❌ Inativo</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Email de Login:</span>
                <span class="info-value"><strong><?php echo htmlspecialchars($usuario_vinculado['email']); ?></strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Já Acessou o Sistema:</span>
                <span class="info-value"><?php echo $usuario_vinculado['ja_acessou']; ?></span>
            </div>
            <?php if ($usuario_vinculado['ultimo_acesso']): ?>
                <div class="info-item">
                    <span class="info-label">Último Acesso:</span>
                    <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($usuario_vinculado['ultimo_acesso'])); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 15px;">
            <a href="../usuarios/editar.php?id=<?php echo $usuario_vinculado['id']; ?>" class="btn btn-primary btn-small">
                ⚙️ Gerenciar Usuário
            </a>
        </div>
    <?php else: ?>
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 8px;">
            <p style="color: #92400e; margin-bottom: 15px;">
                ⚠️ Este aluno ainda não possui acesso ao sistema para visualizar suas aulas.
            </p>
            <a href="criar-usuario.php?aluno_id=<?php echo $id; ?>" class="btn btn-success">
                ➕ Criar Acesso ao Sistema
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="section">
    <h3 class="section-title">Aulas Matriculadas (RF10)</h3>
    
    <?php if ($matriculas && $matriculas->num_rows > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Modalidade</th>
                        <th>Professor</th>
                        <th>Dia da Semana</th>
                        <th>Horário</th>
                        <th>Data Matrícula</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($mat = $matriculas->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mat['modalidade_nome']); ?></td>
                            <td><?php echo htmlspecialchars($mat['professor_nome']); ?></td>
                            <td><?php echo $mat['dia_semana']; ?></td>
                            <td><?php echo date('H:i', strtotime($mat['hora_inicio'])) . ' - ' . date('H:i', strtotime($mat['hora_fim'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($mat['data_matricula'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📅</div>
            <p>Este aluno não está matriculado em nenhuma aula ainda</p>
            <a href="../aulas/matricular.php?aluno_id=<?php echo $id; ?>" class="btn btn-primary">Matricular em Aula</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>