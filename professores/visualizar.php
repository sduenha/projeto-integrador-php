<?php
$titulo = "Visualizar Professor";
$nivel = 1;
include '../includes/header.php';

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID do professor não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Buscar dados do professor
$sql = "SELECT * FROM professores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    definirMensagem('error', 'Professor não encontrado');
    header('Location: index.php');
    exit;
}

$professor = $result->fetch_assoc();
$stmt->close();

// Buscar modalidades vinculadas
$sql = "SELECT m.* FROM modalidades m
        JOIN professor_modalidade pm ON m.id = pm.modalidade_id
        WHERE pm.professor_id = ?
        ORDER BY m.nome";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$modalidades = $stmt->get_result();
$stmt->close();

// Buscar aulas do professor (RF9)
$sql = "SELECT a.*, m.nome as modalidade_nome, m.duracao_minutos,
        (SELECT COUNT(*) FROM matriculas WHERE aula_id = a.id AND ativo = 1) as total_alunos
        FROM aulas a
        JOIN modalidades m ON a.modalidade_id = m.id
        WHERE a.professor_id = ? AND a.ativo = 1
        ORDER BY FIELD(a.dia_semana, 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'), a.hora_inicio";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$aulas = $stmt->get_result();
$stmt->close();
?>

<div class="content-header">
    <h2>Detalhes do Professor</h2>
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
            <span class="info-value"><?php echo htmlspecialchars($professor['nome']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Email:</span>
            <span class="info-value"><?php echo htmlspecialchars($professor['email']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Telefone:</span>
            <span class="info-value"><?php echo htmlspecialchars($professor['telefone']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Especialidade:</span>
            <span class="info-value"><?php echo htmlspecialchars($professor['especialidade']) ?: '-'; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Status:</span>
            <span class="info-value">
                <?php if ($professor['ativo']): ?>
                    <span class="badge badge-success">Ativo</span>
                <?php else: ?>
                    <span class="badge badge-danger">Inativo</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Data de Cadastro:</span>
            <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($professor['data_cadastro'])); ?></span>
        </div>
    </div>
</div>

<div class="section">
    <h3 class="section-title">Modalidades que Leciona</h3>
    
    <?php if ($modalidades && $modalidades->num_rows > 0): ?>
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            <?php while ($mod = $modalidades->fetch_assoc()): ?>
                <span class="badge badge-info" style="font-size: 1rem; padding: 10px 15px;">
                    <?php echo htmlspecialchars($mod['nome']); ?>
                </span>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="color: var(--gray-text);">Nenhuma modalidade vinculada a este professor.</p>
    <?php endif; ?>
</div>

<div class="section">
    <h3 class="section-title">Aulas Ministradas (RF9)</h3>
    
    <?php if ($aulas && $aulas->num_rows > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Modalidade</th>
                        <th>Dia da Semana</th>
                        <th>Horário</th>
                        <th>Duração</th>
                        <th>Vagas Disponíveis</th>
                        <th>Alunos Matriculados</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($aula = $aulas->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($aula['modalidade_nome']); ?></td>
                            <td><?php echo $aula['dia_semana']; ?></td>
                            <td><?php echo date('H:i', strtotime($aula['hora_inicio'])) . ' - ' . date('H:i', strtotime($aula['hora_fim'])); ?></td>
                            <td><?php echo $aula['duracao_minutos']; ?> min</td>
                            <td><?php echo $aula['vagas_disponiveis']; ?></td>
                            <td><strong><?php echo $aula['total_alunos']; ?></strong></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📅</div>
            <p>Este professor não tem aulas cadastradas ainda</p>
            <a href="../aulas/cadastrar.php?professor_id=<?php echo $id; ?>" class="btn btn-primary">Cadastrar Aula</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>