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
$sql = "SELECT * FROM usuarios WHERE id = ?";
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
    } else {
    
        $erros[] = "Email inválido";
    }
    
    if (empty($erros)) {
        if (!empty($senha_nova)) {
            if (strlen($senha_nova) < 6) {
                $erros[] = "A senha deve ter no mínimo 6 caracteres";
            } else {
                $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nome = ?, email = ?, senha = ?, ativo = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssii", $nome, $email, $senha_hash, $ativo, $id);
            }
        } else {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, ativo = ? WHERE id = ?";
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
    
    <div class="form-group">
        <label>
            <input type="checkbox" name="ativo" <?php echo $usuario['ativo'] ? 'checked' : ''; ?>>
            Usuário Ativo
        </label>
    </div>

        <div class="section" style="background: #dbeafe; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; margin-top: 20px;">
        <h3 style="color: #1e40af; margin-bottom: 15px;">🔐 Acesso ao Sistema</h3>
        
        <?php
        // Verificar se já existe usuário vinculado
        $sql = "SELECT id, ativo FROM usuarios WHERE vinculo_id = ? AND tipo_usuario = 'aluno'";
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
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Salvar Alterações</button>
        <a href="index.php" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>