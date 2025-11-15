<?php
$titulo = "Início";
include 'includes/header.php';
?>

<div class="content-header">
    <h2>Bem-vindo ao Sistema de Gestão de Aulas</h2>
</div>

<div class="cards-grid">
    <div class="card">
        <div class="card-icon">👥</div>
        <h3>Alunos</h3>
        <p>Gerencie o cadastro de alunos e suas matrículas</p>
        <a href="alunos/index.php" class="btn btn-primary">Acessar</a>
    </div>

    <div class="card">
        <div class="card-icon">👨‍🏫</div>
        <h3>Professores</h3>
        <p>Controle os professores e suas especialidades</p>
        <a href="professores/index.php" class="btn btn-primary">Acessar</a>
    </div>

    <div class="card">
        <div class="card-icon">🎭</div>
        <h3>Modalidades</h3>
        <p>Cadastre e organize as modalidades de aulas</p>
        <a href="modalidades/index.php" class="btn btn-primary">Acessar</a>
    </div>

    <div class="card">
        <div class="card-icon">📅</div>
        <h3>Aulas</h3>
        <p>Gerencie horários, professores e matrículas</p>
        <a href="aulas/index.php" class="btn btn-primary">Acessar</a>
    </div>
</div>

<div class="section">
    <h3 class="section-title">📊 Estatísticas Rápidas</h3>
    
    <?php
    // Buscar estatísticas
    $stats = [];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM alunos WHERE ativo = 1");
    $stats['alunos'] = $result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM professores WHERE ativo = 1");
    $stats['professores'] = $result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM modalidades WHERE ativo = 1");
    $stats['modalidades'] = $result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM aulas WHERE ativo = 1");
    $stats['aulas'] = $result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM matriculas WHERE ativo = 1");
    $stats['matriculas'] = $result->fetch_assoc()['total'];
    ?>
    
    <div class="info-list">
        <div class="info-item">
            <span class="info-label">Alunos</span>
            <span class="info-value"><strong><?php echo $stats['alunos']; ?></strong></span>
        </div>
        <div class="info-item">
            <span class="info-label">Professores</span>
            <span class="info-value"><strong><?php echo $stats['professores']; ?></strong></span>
        </div>
        <div class="info-item">
            <span class="info-label">Modalidades</span>
            <span class="info-value"><strong><?php echo $stats['modalidades']; ?></strong></span>
        </div>
        <div class="info-item">
            <span class="info-label">Aulas</span>
            <span class="info-value"><strong><?php echo $stats['aulas']; ?></strong></span>
        </div>
        <div class="info-item">
            <span class="info-label">Matrículas Ativas</span>
            <span class="info-value"><strong><?php echo $stats['matriculas']; ?></strong></span>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>