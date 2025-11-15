<?php
$titulo = "Aulas";
$nivel = 1;
include '../includes/header.php';

// Buscar todas as aulas com informações relacionadas
$sql = "SELECT a.*, 
        m.nome as modalidade_nome, 
        p.nome as professor_nome,
        (SELECT COUNT(*) FROM matriculas WHERE aula_id = a.id AND ativo = 1) as total_alunos
        FROM aulas a
        JOIN modalidades m ON a.modalidade_id = m.id
        JOIN professores p ON a.professor_id = p.id
        ORDER BY FIELD(a.dia_semana, 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'), 
                 a.hora_inicio ASC";
$result = $conn->query($sql);
?>

<div class="content-header">
    <h2>Gerenciamento de Aulas</h2>
    <a href="cadastrar.php" class="btn btn-primary">➕ Nova Aula</a>
</div>

<?php if ($result && $result->num_rows > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Modalidade</th>
                    <th>Professor</th>
                    <th>Dia da Semana</th>
                    <th>Horário</th>
                    <th>Vagas</th>
                    <th>Matriculados</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($aula = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $aula['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($aula['modalidade_nome']); ?></strong></td>
                        <td><?php echo htmlspecialchars($aula['professor_nome']); ?></td>
                        <td><?php echo $aula['dia_semana']; ?></td>
                        <td><?php echo date('H:i', strtotime($aula['hora_inicio'])) . ' - ' . date('H:i', strtotime($aula['hora_fim'])); ?></td>
                        <td><?php echo $aula['vagas_disponiveis']; ?></td>
                        <td>
                            <span class="badge badge-info"><?php echo $aula['total_alunos']; ?></span>
                        </td>
                        <td>
                            <?php if ($aula['ativo']): ?>
                                <span class="badge badge-success">Ativa</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativa</span>
                            <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <a href="matricular.php?aula_id=<?php echo $aula['id']; ?>" class="btn btn-success btn-small">➕ Matricular</a>
                            <a href="editar.php?id=<?php echo $aula['id']; ?>" class="btn btn-primary btn-small">✏️ Editar</a>
                            <a href="excluir.php?id=<?php echo $aula['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Tem certeza que deseja excluir esta aula?')">🗑️ Excluir</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">📅</div>
        <p>Nenhuma aula cadastrada ainda</p>
        <a href="cadastrar.php" class="btn btn-primary">Cadastrar Primeira Aula</a>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>