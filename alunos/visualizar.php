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

// Buscar dados do aluno com endereço
$sql = "SELECT a.*, e.endereco, e.bairro, e.cep, e.numero
        FROM alunos a
        LEFT JOIN enderecos e ON a.id_endereco = e.id_endereco
        WHERE a.id_aluno = ?";
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

// Buscar aulas matriculadas (RF10)
$sql = "SELECT m.*, 
        a.id_aula, a.vagas_disponiveis,
        mo.nome as modalidade_nome, mo.duracao_minutos,
        p.nome_professor as professor_nome,
        GROUP_CONCAT(CONCAT(ah.dia_semana, ' ', TIME_FORMAT(ah.hora_inicio, '%H:%i'), '-', TIME_FORMAT(ah.hora_fim, '%H:%i')) SEPARATOR ', ') as horarios
        FROM matriculas m
        JOIN aulas a ON m.aula_id = a.id_aula
        JOIN modalidades mo ON a.modalidade_id = mo.id_modalidade
        JOIN professores p ON a.professor_id = p.id_professor
        LEFT JOIN aula_horario ah ON a.id_aula = ah.id_aula
        WHERE m.aluno_id = ? AND m.ativo = 1
        GROUP BY m.id_matricula
        ORDER BY mo.nome";
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
            <span class="info-value"><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></span>
        </div>
        <div class="info-item">
            <span class="info-label">Email:</span>
            <span class="info-value"><?php echo htmlspecialchars($aluno['email']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Telefone:</span>
            <span class="info-value"><?php echo htmlspecialchars($aluno['telefone']) ?: '-'; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Data de Nascimento:</span>
            <span class="info-value"><?php echo $aluno['data_nascimento'] ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '-'; ?></span>
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
    </div>
</div>

<div class="section">
    <h3 class="section-title">Endereço</h3>
    
    <?php if (!empty($aluno['endereco']) || !empty($aluno['bairro'])): ?>
        <div class="info-list">
            <?php if ($aluno['endereco']): ?>
                <div class="info-item">
                    <span class="info-label">Logradouro:</span>
                    <span class="info-value"><?php echo htmlspecialchars($aluno['endereco']); ?><?php echo $aluno['numero'] ? ', ' . htmlspecialchars($aluno['numero']) : ''; ?></span>
                </div>
            <?php endif; ?>
            <?php if ($aluno['bairro']): ?>
                <div class="info-item">
                    <span class="info-label">Bairro:</span>
                    <span class="info-value"><?php echo htmlspecialchars($aluno['bairro']); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($aluno['cep']): ?>
                <div class="info-item">
                    <span class="info-label">CEP:</span>
                    <span class="info-value"><?php echo htmlspecialchars($aluno['cep']); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p style="color: var(--gray-text);">Endereço não cadastrado</p>
    <?php endif; ?>
</div>

<div class="section">
    <h3 class="section-title">🔐 Acesso ao Sistema</h3>
    
    <?php
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
    <h3 class="section-title">Aulas Matriculadas</h3>
    
    <?php if ($matriculas && $matriculas->num_rows > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Modalidade</th>
                        <th>Professor</th>
                        <th>Horários</th>
                        <th>Duração</th>
                        <th>Data da Matrícula</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($mat = $matriculas->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($mat['modalidade_nome']); ?></strong></td>
                            <td><?php echo htmlspecialchars($mat['professor_nome']); ?></td>
                            <td><?php echo $mat['horarios'] ?: 'Sem horários'; ?></td>
                            <td><?php echo $mat['duracao_minutos']; ?> min</td>
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