<?php
$titulo = "Cadastrar Modalidade";
$nivel = 1;
include '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizarDados($conn, $_POST['nome']);
    $descricao = sanitizarDados($conn, $_POST['descricao']);
    $duracao_minutos = (int)$_POST['duracao_minutos'];
    $vagas_maximas = (int)$_POST['vagas_maximas'];
    
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório";
    }
    
    if ($duracao_minutos <= 0) {
        $erros[] = "A duração deve ser maior que zero";
    }
    
    if ($vagas_maximas <= 0) {
        $erros[] = "O número de vagas deve ser maior que zero";
    }
    
    if (empty($erros)) {
        $sql = "INSERT INTO modalidades (nome, descricao, duracao_minutos, vagas_maximas) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $nome, $descricao, $duracao_minutos, $vagas_maximas);
        
        if ($stmt->execute()) {
            definirMensagem('success', 'Modalidade cadastrada com sucesso!');
            header('Location: index.php');
            exit;
        } else {
            $erros[] = "Erro ao cadastrar modalidade: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "<div class='mensagem mensagem-erro'>{$erro}</div>";
        }
    }
}
?>

<div class="content-header">
    <h2>Cadastrar Nova Modalidade</h2>
</div>

<form method="POST" action="">
    <div class="form-group">
        <label for="nome">Nome da Modalidade *</label>
        <input type="text" id="nome" name="nome" required placeholder="Ex: Yoga, Pilates, Ballet" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
    </div>
    
    <div class="form-group">
        <label for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao" placeholder="Descreva a modalidade..."><?php echo isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : ''; ?></textarea>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="duracao_minutos">Duração (minutos) *</label>
            <input type="number" id="duracao_minutos" name="duracao_minutos" required min="1" value="<?php echo isset($_POST['duracao_minutos']) ? $_POST['duracao_minutos'] : '60'; ?>">
        </div>
        
        <div class="form-group">
            <label for="vagas_maximas">Vagas Máximas *</label>
            <input type="number" id="vagas_maximas" name="vagas_maximas" required min="1" value="<?php echo isset($_POST['vagas_maximas']) ? $_POST['vagas_maximas'] : '20'; ?>">
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Cadastrar</button>
        <a href="index.php" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>