<?php
$titulo = "Editar Modalidade";
$nivel = 1;
include '../includes/header.php';

if (!isProprietario()) {
    definirMensagem('error', 'Acesso negado! Apenas proprietários podem gerenciar modalidades.');
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID da modalidade não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Buscar dados da modalidade
$sql = "SELECT * FROM modalidades WHERE id_modalidade = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    definirMensagem('error', 'Modalidade não encontrada');
    header('Location: index.php');
    exit;
}

$modalidade = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizarDados($conn, $_POST['nome']);
    $descricao = sanitizarDados($conn, $_POST['descricao']);
    $duracao_minutos = (int)$_POST['duracao_minutos'];
    $vagas_maximas = (int)$_POST['vagas_maximas'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
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
        $sql = "UPDATE modalidades SET nome = ?, descricao = ?, duracao_minutos = ?, vagas_maximas = ?, ativo = ? WHERE id_modalidade = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiiii", $nome, $descricao, $duracao_minutos, $vagas_maximas, $ativo, $id);
        
        if ($stmt->execute()) {
            definirMensagem('success', 'Modalidade atualizada com sucesso!');
            header('Location: index.php');
            exit;
        } else {
            $erros[] = "Erro ao atualizar modalidade: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "<div class='mensagem mensagem-erro'>{$erro}</div>";
        }
    }
} else {
    $_POST = $modalidade;
}
?>

<div class="content-header">
    <h2>Editar Modalidade</h2>
</div>

<form method="POST" action="">
    <div class="form-group">
        <label for="nome">Nome da Modalidade *</label>
        <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($_POST['nome']); ?>">
    </div>
    
    <div class="form-group">
        <label for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao"><?php echo htmlspecialchars($_POST['descricao']); ?></textarea>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="duracao_minutos">Duração (minutos) *</label>
            <input type="number" id="duracao_minutos" name="duracao_minutos" required min="1" 
                   value="<?php echo $_POST['duracao_minutos']; ?>">
        </div>
        
        <div class="form-group">
            <label for="vagas_maximas">Vagas Máximas *</label>
            <input type="number" id="vagas_maximas" name="vagas_maximas" required min="1" 
                   value="<?php echo $_POST['vagas_maximas']; ?>">
        </div>
    </div>
    
    <div class="form-group">
        <label>
            Modalidade Ativa
            <input type="checkbox" name="ativo" <?php echo $modalidade['ativo'] ? 'checked' : ''; ?>>
        </label>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Salvar Alterações</button>
        <a href="index.php" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>