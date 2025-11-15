<?php
$titulo = "Alunos";
$nivel = 1;
include '../includes/header.php';

// Buscar todos os alunos
$sql = "SELECT * FROM alunos ORDER BY nome ASC";
$result = $conn->query($sql);
?>

<div class="content-header">
    <h2>Gerenciamento de Alunos</h2>
    <a href="cadastrar.php" class="btn btn-primary">➕ Novo Aluno</a>
</div>

<?php if ($result && $result->num_rows > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($aluno = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $aluno['id']; ?></td>
                        <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                        <td><?php echo htmlspecialchars($aluno['email']); ?></td>
                        <td><?php echo htmlspecialchars($aluno['telefone']); ?></td>
                        <td>
                            <?php if ($aluno['ativo']): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <a href="visualizar.php?id=<?php echo $aluno['id']; ?>" class="btn btn-secondary btn-small">👁️ Ver</a>
                            <a href="editar.php?id=<?php echo $aluno['id']; ?>" class="btn btn-primary btn-small">✏️ Editar</a>
                            <a href="excluir.php?id=<?php echo $aluno['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Tem certeza que deseja excluir este aluno?')">🗑️ Excluir</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">👥</div>
        <p>Nenhum aluno cadastrado ainda</p>
        <a href="cadastrar.php" class="btn btn-primary">Cadastrar Primeiro Aluno</a>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>