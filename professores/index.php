<?php
$titulo = "Professores";
$nivel = 1;
include '../includes/header.php';

// Buscar todos os professores
$sql = "SELECT * FROM professores ORDER BY nome ASC";
$result = $conn->query($sql);
?>

<div class="content-header">
    <h2>Gerenciamento de Professores</h2>
    <a href="cadastrar.php" class="btn btn-primary">➕ Novo Professor</a>
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
                    <th>Especialidade</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($professor = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $professor['id']; ?></td>
                        <td><?php echo htmlspecialchars($professor['nome']); ?></td>
                        <td><?php echo htmlspecialchars($professor['email']); ?></td>
                        <td><?php echo htmlspecialchars($professor['telefone']); ?></td>
                        <td><?php echo htmlspecialchars($professor['especialidade']); ?></td>
                        <td>
                            <?php if ($professor['ativo']): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <a href="visualizar.php?id=<?php echo $professor['id']; ?>" class="btn btn-secondary btn-small">👁️ Ver</a>
                            <a href="editar.php?id=<?php echo $professor['id']; ?>" class="btn btn-primary btn-small">✏️ Editar</a>
                            <a href="excluir.php?id=<?php echo $professor['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Tem certeza que deseja excluir este professor?')">🗑️ Excluir</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">👨‍🏫</div>
        <p>Nenhum professor cadastrado ainda</p>
        <a href="cadastrar.php" class="btn btn-primary">Cadastrar Primeiro Professor</a>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>