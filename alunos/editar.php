<?php
$titulo = "Editar Aluno";
$nivel = 1;
include '../includes/header.php';

// Verificar se ID foi passado
if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID do aluno não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Buscar dados do aluno
$sql = "SELECT * FROM alunos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    definirMensagem('error', 'Aluno não encontrado');
    header('Location: index.php');
    exit;
}

$aluno = $result->fetch_assoc();
$stmt->close();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizarDados($conn, $_POST['nome']);
    $email = sanitizarDados($conn, $_POST['email']);
    $telefone = sanitizarDados($conn, $_POST['telefone']);
    $data_nascimento = sanitizarDados($conn, $_POST['data_nascimento']);
    $endereco = sanitizarDados($conn, $_POST['endereco']);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório";
    }
    
    if (empty($email)) {
        $erros[] = "O email é obrigatório";
    } elseif (!validarEmail($email)) {
        $erros[] = "Email inválido";
    } else {
        // Verificar se email já existe (exceto para este aluno)
        $sql = "SELECT id FROM alunos WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $erros[] = "Este email já está cadastrado para outro aluno";
        }
        $stmt->close();
    }
    
    if (empty($erros)) {
        $sql = "UPDATE alunos SET nome = ?, email = ?, telefone = ?, data_nascimento = ?, endereco = ?, ativo = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssii", $nome, $email, $telefone, $data_nascimento, $endereco, $ativo, $id);
        
        if ($stmt->execute()) {
            definirMensagem('success', 'Aluno atualizado com sucesso!');
            header('Location: index.php');
            exit;
        } else {
            $erros[] = "Erro ao atualizar aluno: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "<div class='mensagem mensagem-erro'>{$erro}</div>";
        }
    }
} else {
    // Preencher variáveis com dados do banco
    $_POST = $aluno;
}
?>

<div class="content-header">
    <h2>Editar Aluno</h2>
</div>

<form method="POST" action="">
    <div class="form-row">
        <div class="form-group">
            <label for="nome">Nome Completo *</label>
            <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($_POST['nome']); ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email']); ?>">
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($_POST['telefone']); ?>">
        </div>
        
        <div class="form-group">
            <label for="data_nascimento">Data de Nascimento</label>
            <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo $_POST['data_nascimento']; ?>">
        </div>
    </div>
    
    <div class="form-group">
        <label for="endereco">Endereço</label>
        <textarea id="endereco" name="endereco"><?php echo htmlspecialchars($_POST['endereco']); ?></textarea>
    </div>
    
    <div class="form-group">
        <label>
            <input type="checkbox" name="ativo" <?php echo $aluno['ativo'] ? 'checked' : ''; ?>>
            Aluno Ativo
        </label>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Salvar Alterações</button>
        <a href="index.php" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>