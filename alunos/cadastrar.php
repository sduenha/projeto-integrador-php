<?php
$titulo = "Cadastrar Aluno";
$nivel = 1;
include '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizarDados($conn, $_POST['nome']);
    $email = sanitizarDados($conn, $_POST['email']);
    $telefone = sanitizarDados($conn, $_POST['telefone']);
    $data_nascimento = sanitizarDados($conn, $_POST['data_nascimento']);
    $endereco = sanitizarDados($conn, $_POST['endereco']);
    
    // Validações
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório";
    }
    
    if (empty($email)) {
        $erros[] = "O email é obrigatório";
    } elseif (!validarEmail($email)) {
        $erros[] = "Email inválido";
    } else {
        // Verificar se email já existe
        $sql = "SELECT id FROM alunos WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $erros[] = "Este email já está cadastrado";
        }
        $stmt->close();
    }
    
    if (empty($erros)) {
        $sql = "INSERT INTO alunos (nome, email, telefone, data_nascimento, endereco) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $nome, $email, $telefone, $data_nascimento, $endereco);
        
        if ($stmt->execute()) {
            definirMensagem('success', 'Aluno cadastrado com sucesso!');
            header('Location: index.php');
            exit;
        } else {
            $erros[] = "Erro ao cadastrar aluno: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Exibir erros
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "<div class='mensagem mensagem-erro'>{$erro}</div>";
        }
    }
}
?>

<div class="content-header">
    <h2>Cadastrar Novo Aluno</h2>
</div>

<form method="POST" action="">
    <div class="form-row">
        <div class="form-group">
            <label for="nome">Nome Completo *</label>
            <input type="text" id="nome" name="nome" required value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000" value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="data_nascimento">Data de Nascimento</label>
            <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo isset($_POST['data_nascimento']) ? $_POST['data_nascimento'] : ''; ?>">
        </div>
    </div>
    
    <div class="form-group">
        <label for="endereco">Endereço</label>
        <textarea id="endereco" name="endereco"><?php echo isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : ''; ?></textarea>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Cadastrar</button>
        <a href="index.php" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>