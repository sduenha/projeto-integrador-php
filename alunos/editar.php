<?php
$titulo = "Editar Aluno";
$nivel = 1;
include '../includes/header.php';

if (!isProprietario()) {
    definirMensagem('error', 'Acesso negado! Apenas proprietários podem gerenciar modalidades.');
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID do aluno não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Buscar dados do aluno com endereço
$sql = "SELECT a.*, e.endereco, e.bairro, e.cep, e.numero, e.id_endereco
        FROM alunos a
        LEFT JOIN enderecos e ON a.id_endereco = e.id_endereco
        WHERE a.id_aluno = ?";
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizarDados($conn, $_POST['nome']);
    $email = sanitizarDados($conn, $_POST['email']);
    $telefone = sanitizarDados($conn, $_POST['telefone']);
    $data_nascimento = sanitizarDados($conn, $_POST['data_nascimento']);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Dados do endereço
    $endereco = sanitizarDados($conn, $_POST['endereco']);
    $bairro = sanitizarDados($conn, $_POST['bairro']);
    $cep = sanitizarDados($conn, $_POST['cep']);
    $numero = sanitizarDados($conn, $_POST['numero']);
    
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório";
    }
    
    if (empty($email)) {
        $erros[] = "O email é obrigatório";
    } elseif (!validarEmail($email)) {
        $erros[] = "Email inválido";
    } else {
        // Verificar se email já existe para outro aluno
        $sql = "SELECT id_aluno FROM alunos WHERE email = ? AND id_aluno != ?";
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
        $conn->begin_transaction();
        
        try {
            $id_endereco = $aluno['id_endereco'];
            
            // 1. Atualizar ou criar endereço
            if (!empty($endereco) || !empty($bairro) || !empty($cep) || !empty($numero)) {
                if ($id_endereco) {
                    // Atualizar endereço existente
                    $sql = "UPDATE enderecos SET endereco = ?, bairro = ?, cep = ?, numero = ? WHERE id_endereco = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssi", $endereco, $bairro, $cep, $numero, $id_endereco);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // Criar novo endereço
                    $sql = "INSERT INTO enderecos (endereco, bairro, cep, numero) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $endereco, $bairro, $cep, $numero);
                    $stmt->execute();
                    $id_endereco = $conn->insert_id;
                    $stmt->close();
                }
            } elseif ($id_endereco) {
                // Se todos os campos de endereço foram apagados, remover o vínculo
                $id_endereco = null;
            }
            
            // 2. Atualizar aluno
            $sql = "UPDATE alunos SET nome = ?, email = ?, telefone = ?, data_nascimento = ?, id_endereco = ?, ativo = ? WHERE id_aluno = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssii", $nome, $email, $telefone, $data_nascimento, $id_endereco, $ativo, $id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            definirMensagem('success', 'Aluno atualizado com sucesso!');
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $erros[] = "Erro ao atualizar aluno: " . $e->getMessage();
        }
    }
    
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "<div class='mensagem mensagem-erro'>{$erro}</div>";
        }
    }
} else {
    $_POST = $aluno;
}
?>

<div class="content-header">
    <h2>Editar Aluno</h2>
</div>

<form method="POST" action="">
    <div class="section">
        <h3 class="section-title">Dados Pessoais</h3>
        
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
    </div>
    
    <div class="section">
        <h3 class="section-title">Endereço</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="cep">CEP</label>
                <input type="text" id="cep" name="cep" value="<?php echo htmlspecialchars($_POST['cep']); ?>">
            </div>
            
            <div class="form-group">
                <label for="bairro">Bairro</label>
                <input type="text" id="bairro" name="bairro" value="<?php echo htmlspecialchars($_POST['bairro']); ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group" style="flex: 3;">
                <label for="endereco">Endereço</label>
                <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($_POST['endereco']); ?>">
            </div>
            
            <div class="form-group" style="flex: 1;">
                <label for="numero">Número</label>
                <input type="text" id="numero" name="numero" value="<?php echo htmlspecialchars($_POST['numero']); ?>">
            </div>
        </div>
    </div>
    
    <div class="section" style="background: #dbeafe; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6;">
        <h3 style="color: #1e40af; margin-bottom: 15px;">🔐 Acesso ao Sistema</h3>
        
        <?php
        // Verificar se já existe usuário vinculado
        $sql = "SELECT id_usuario, ativo FROM usuarios WHERE vinculo_id = ? AND tipo_usuario = 'aluno'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario_vinculado = $result->fetch_assoc();
        $stmt->close();
        ?>
        
        <?php if ($usuario_vinculado): ?>
            <p style="color: #059669; margin-bottom: 10px;">
                ✅ Este aluno já possui acesso ao sistema.
            </p>
            <p style="color: #1e40af;">
                <strong>Email de login:</strong> <?php echo htmlspecialchars($aluno['email']); ?>
            </p>
            <p style="color: #6b7280; font-size: 0.9rem; margin-top: 10px;">
                Status: 
                <?php if ($usuario_vinculado['ativo']): ?>
                    <span class="badge badge-success">Ativo</span>
                <?php else: ?>
                    <span class="badge badge-danger">Inativo</span>
                <?php endif; ?>
            </p>
            <a href="../usuarios/editar.php?id=<?php echo $usuario_vinculado['id']; ?>" class="btn btn-primary btn-small" style="margin-top: 10px;">
                Gerenciar Usuário
            </a>
        <?php else: ?>
            <p style="color: #92400e; margin-bottom: 15px;">
                ⚠️ Este aluno ainda não possui acesso ao sistema.
            </p>
            <a href="criar-usuario.php?aluno_id=<?php echo $id; ?>" class="btn btn-success">
                ➕ Criar Acesso ao Sistema
            </a>
        <?php endif; ?>
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