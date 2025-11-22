<?php
$titulo = "Alunos";
$nivel = 1;
include '../includes/header.php';

// Buscar todos os alunos
$sql = "SELECT a.*, 
        e.endereco, e.bairro, e.cep, e.numero,
        (SELECT COUNT(*) FROM matriculas WHERE aluno_id = a.id_aluno AND ativo = 1) as total_matriculas
        FROM alunos a
        LEFT JOIN enderecos e ON a.id_endereco = e.id_endereco
        ORDER BY a.nome ASC";
$result = $conn->query($sql);
?>

<div class="content-header">
    <h2>Gerenciamento de Alunos</h2>
    <?php if (isProprietario()): ?>
        <a href="cadastrar.php" class="btn btn-primary">➕ Novo Aluno</a>
    <?php endif; ?>
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
                    <th>Bairro</th>
                    <th>Matrículas</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($aluno = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $aluno['id_aluno']; ?></td>
                        <td><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></td>
                        <td><?php echo htmlspecialchars($aluno['email']); ?></td>
                        <td><?php echo htmlspecialchars($aluno['telefone']); ?></td>
                        <td><?php echo htmlspecialchars($aluno['bairro']) ?: '-'; ?></td>
                        <td>
                            <span class="badge badge-info"><?php echo $aluno['total_matriculas']; ?></span>
                        </td>
                        <td>
                            <?php if ($aluno['ativo']): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <a href="visualizar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-secondary btn-small">👁️ Ver</a>
                            <?php if (isProprietario()): ?>
                                <a href="editar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-primary btn-small">✏️ Editar</a>
                                <a href="excluir.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Tem certeza que deseja excluir este aluno?')">🗑️ Excluir</a>
                            <?php endif; ?>
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
        <?php if (isProprietario()): ?>
            <a href="cadastrar.php" class="btn btn-primary">Cadastrar Primeiro Aluno</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>