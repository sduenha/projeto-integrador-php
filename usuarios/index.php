<?php
$titulo = "Usuários";
$nivel = 1;
include '../includes/header.php';

// Apenas proprietário pode acessar
if (!isProprietario()) {
    definirMensagem('error', 'Acesso negado! Apenas proprietários podem gerenciar usuários.');
    header('Location: ../index.php');
    exit;
}

// Buscar todos os usuários
$sql = "SELECT u.*, 
        CASE 
            WHEN u.tipo_usuario = 'professor' THEN p.nome
            WHEN u.tipo_usuario = 'aluno' THEN a.nome
            ELSE NULL
        END as nome_vinculo
        FROM usuarios u
        LEFT JOIN professores p ON u.vinculo_id = p.id AND u.tipo_usuario = 'professor'
        LEFT JOIN alunos a ON u.vinculo_id = a.id AND u.tipo_usuario = 'aluno'
        ORDER BY u.tipo_usuario, u.nome";
$result = $conn->query($sql);
?>

<div class="content-header">
    <h2>Gerenciamento de Usuários</h2>
    <a href="cadastrar.php" class="btn btn-primary">➕ Novo Usuário</a>
</div>

<?php if ($result && $result->num_rows > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Vínculo</th>
                    <th>Último Acesso</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($usuario = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $usuario['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($usuario['nome']); ?></strong></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo obterNomeTipoUsuario($usuario['tipo_usuario']); ?>
                            </span>
                        </td>
                        <td><?php echo $usuario['nome_vinculo'] ? htmlspecialchars($usuario['nome_vinculo']) : '-'; ?></td>
                        <td><?php echo $usuario['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) : 'Nunca'; ?></td>
                        <td>
                            <?php if ($usuario['ativo']): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <a href="editar.php?id=<?php echo $usuario['id']; ?>" class="btn btn-primary btn-small">✏️ Editar</a>
                            <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                <a href="excluir.php?id=<?php echo $usuario['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Tem certeza que deseja excluir este usuário?')">🗑️ Excluir</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">🔐</div>
        <p>Nenhum usuário cadastrado ainda</p>
        <a href="cadastrar.php" class="btn btn-primary">Cadastrar Primeiro Usuário</a>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>