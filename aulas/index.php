<?php
$titulo = "Aulas";
$nivel = 1;
include '../includes/header.php';

// Buscar todas as aulas com seus horários
$sql = "SELECT a.*, a.professor_id, 
        m.nome as modalidade_nome, 
        p.nome_professor as professor_nome,
        (SELECT COUNT(*) FROM matriculas WHERE aula_id = a.id_aula AND ativo = 1) as total_alunos,
        GROUP_CONCAT(CONCAT(ah.dia_semana, ' ', TIME_FORMAT(ah.hora_inicio, '%H:%i'), '-', TIME_FORMAT(ah.hora_fim, '%H:%i')) SEPARATOR ', ') as horarios
        FROM aulas a
        JOIN modalidades m ON a.modalidade_id = m.id_modalidade
        JOIN professores p ON a.professor_id = p.id_professor
        LEFT JOIN aula_horario ah ON a.id_aula = ah.id_aula
        GROUP BY a.id_aula
        ORDER BY m.nome, p.nome_professor";
$result = $conn->query($sql);
?>

<div class="content-header">
    <h2>Gerenciamento de Aulas</h2>
    <?php if (isProprietario()): ?>
        <a href="cadastrar.php" class="btn btn-primary">➕ Nova Aula</a>
    <?php endif; ?>
</div>

<?php if ($result && $result->num_rows > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Modalidade</th>
                    <th>Professor</th>
                    <th>Horários</th>
                    <th>Vagas</th>
                    <th>Matriculados</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($aula = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $aula['id_aula']; ?></td>
                        <td><strong><?php echo htmlspecialchars($aula['modalidade_nome']); ?></strong></td>
                        <td><?php echo htmlspecialchars($aula['professor_nome']); ?></td>
                        <td><?php echo $aula['horarios'] ?: 'Sem horários'; ?></td>
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
                            <?php if (isProprietario()): ?>
                                <a href="matricular.php?aula_id=<?php echo $aula['id_aula']; ?>" class="btn btn-success btn-small">➕ Matricular</a>
                                <a href="editar.php?id=<?php echo $aula['id_aula']; ?>" class="btn btn-primary btn-small">✏️ Editar</a>
                                <a href="excluir.php?id=<?php echo $aula['id_aula']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Tem certeza que deseja excluir esta aula?')">🗑️ Excluir</a>
                            <?php elseif (isProfessor() && isset($_SESSION['vinculo_id']) && $aula['professor_id'] == $_SESSION['vinculo_id']): ?>
                                <a href="matricular.php?aula_id=<?php echo $aula['id_aula']; ?>" class="btn btn-success btn-small">➕ Matricular</a>
                            <?php else: ?>
                                <span style="color: var(--gray-text); font-size: 0.9rem;">Visualização apenas</span>
                            <?php endif; ?>
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
        <?php if (isProprietario()): ?>
            <a href="cadastrar.php" class="btn btn-primary">Cadastrar Primeira Aula</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>