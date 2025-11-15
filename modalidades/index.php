<?php
$titulo = "Modalidades";
$nivel = 1;
include '../includes/header.php';

// Buscar todas as modalidades
$sql = "SELECT m.*, 
        (SELECT COUNT(*) FROM aulas WHERE modalidade_id = m.id AND ativo = 1) as total_aulas
        FROM modalidades m 
        ORDER BY m.nome ASC";
$result = $conn->query($sql);
?>

<div class="content-header">
    <h2>Gerenciamento de Modalidades</h2>
    <a href="cadastrar.php" class="btn btn-primary">➕ Nova Modalidade</a>
</div>

<?php if ($result && $result->num_rows > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Duração</th>
                    <th>Vagas Máximas</th>
                    <th>Total de Aulas</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($modalidade = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $modalidade['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($modalidade['nome']); ?></strong></td>
                        <td><?php echo $modalidade['duracao_minutos']; ?> min</td>
                        <td><?php echo $modalidade['vagas_maximas']; ?></td>
                        <td><?php echo $modalidade['total_aulas']; ?></td>
                        <td>
                            <?php if ($modalidade['ativo']): ?>
                                <span class="badge badge-success">Ativa</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativa</span>
                            <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <a href="editar.php?id=<?php echo $modalidade['id']; ?>" class="btn btn-primary btn-small">✏️ Editar</a>
                            <a href="excluir.php?id=<?php echo $modalidade['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Tem certeza que deseja excluir esta modalidade?')">🗑️ Excluir</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">🎭</div>
        <p>Nenhuma modalidade cadastrada ainda</p>
        <a href="cadastrar.php" class="btn btn-primary">Cadastrar Primeira Modalidade</a>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>