<?php
$titulo = "Editar Usuário";
$nivel = 1;
include '../includes/header.php';

if (!isProprietario()) {
    definirMensagem('error', 'Acesso negado!');
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID do usuário não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Buscar usuário
$sql = "SELECT * FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    definirMensagem('error', 'Usuário não encontrado');
    header('Location: index.php');
    exit;
}

$usuario = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizarDados($conn, $_POST['nome']);
    $email = sanitizarDados($conn, $_POST['email']);
    $senha_nova = $_POST['senha_nova'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório";
    }
    
    if (empty($email) || !validarEmail($email)) {
        $erros[] = "Email inválido";
    }
    
    if (empty($erros)) {
        if (!empty($senha_nova)) {
            if (strlen($senha_nova) < 6) {
                $erros[] = "A senha deve ter no mínimo 6 caracteres";
            } else {
                $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nome = ?, email = ?, senha = ?, ativo = ? WHERE id_usuario = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssii", $nome, $email, $senha_hash, $ativo, $id);
            }
        } else {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, ativo = ? WHERE id_usuario = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $nome, $email, $ativo, $id);
        }
        
        if (empty($erros) && $stmt->execute()) {
            definirMensagem('success', 'Usuário atualizado com sucesso!');
            header('Location: index.php');
            exit;
        } else {
            $erros[] = "Erro ao atualizar usuário";
        }
        $stmt->close();
    }
    
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "<div class='mensagem mensagem-erro'>{$erro}</div>";
        }
    }
} else {
    $_POST = $usuario;
}
?>

<div class="content-header">
    <h2>Editar Usuário</h2>
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
    
    <div class="form-group">
        <label for="senha_nova">Nova Senha (deixe em branco para manter a atual)</label>
        <input type="password" id="senha_nova" name="senha_nova" minlength="6" placeholder="Mínimo 6 caracteres">
    </div>
    
    <div class="section" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
        <h4 style="margin-bottom: 10px;">Informações do Usuário</h4>
        <div class="info-list">
            <div class="info-item">
                <span class="info-label">Tipo:</span>
                <span class="info-value">
                    <span class="badge badge-info"><?php echo obterNomeTipoUsuario($usuario['tipo_usuario']); ?></span>
                </span>
            </div>
            <?php if ($usuario['vinculo_id']): ?>
                <div class="info-item">
                    <span class="info-label">Vínculo ID:</span>
                    <span class="info-value"><?php echo $usuario['vinculo_id']; ?></span>
                </div>
            <?php endif; ?>
            <div class="info-item">
                <span class="info-label">Cadastrado em:</span>
                <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?></span>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label>
            <input type="checkbox" name="ativo" <?php echo $usuario['ativo'] ? 'checked' : ''; ?>>
            Usuário Ativo
        </label>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Salvar Alterações</button>
        <a href="index.php" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>