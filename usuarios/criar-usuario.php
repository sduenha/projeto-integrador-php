<?php
$titulo = "Criar Acesso ao Sistema";
$nivel = 1;
include '../includes/header.php';

if (!isset($_GET['aluno_id'])) {
    definirMensagem('error', 'ID do aluno não informado');
    header('Location: index.php');
    exit;
}

$aluno_id = (int)$_GET['aluno_id'];

// Buscar dados do aluno
$sql = "SELECT * FROM alunos WHERE id_aluno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    definirMensagem('error', 'Aluno não encontrado');
    header('Location: index.php');
    exit;
}

$aluno = $result->fetch_assoc();
$stmt->close();

// Verificar se já existe usuário
$sql = "SELECT id_usuario FROM usuarios WHERE vinculo_id = ? AND tipo_usuario = 'aluno'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    definirMensagem('warning', 'Este aluno já possui acesso ao sistema');
    header('Location: editar.php?id=' . $aluno_id);
    exit;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_inicial = $_POST['senha_inicial'];
    
    $erros = [];
    
    if (empty($senha_inicial)) {
        $erros[] = "A senha inicial é obrigatória";
    } elseif (strlen($senha_inicial) < 6) {
        $erros[] = "A senha deve ter no mínimo 6 caracteres";
    }
    
    if (empty($erros)) {
        $senha_hash = password_hash($senha_inicial, PASSWORD_DEFAULT);
        $tipo_usuario = 'aluno';
        
        $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, vinculo_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $aluno['nome'], $aluno['email'], $senha_hash, $tipo_usuario, $aluno_id);
        
        if ($stmt->execute()) {
            definirMensagem('success', "Acesso criado com sucesso!<br><strong>Email:</strong> {$aluno['email']}<br><strong>Senha:</strong> {$senha_inicial}");
            header('Location: editar.php?id=' . $aluno_id);
            exit;
        } else {
            $erros[] = "Erro ao criar usuário: " . $stmt->error;
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
    <h2>Criar Acesso ao Sistema</h2>
</div>

<div class="section">
    <h3 class="section-title">Informações do Aluno</h3>
    <div class="info-list">
        <div class="info-item">
            <span class="info-label">Nome:</span>
            <span class="info-value"><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></span>
        </div>
        <div class="info-item">
            <span class="info-label">Email:</span>
            <span class="info-value"><?php echo htmlspecialchars($aluno['email']); ?></span>
        </div>
    </div>
</div>

<div class="mensagem mensagem-info">
    ℹ️ Ao criar o acesso, o aluno poderá fazer login com o <strong>email cadastrado</strong> e a <strong>senha definida abaixo</strong>.
</div>

<form method="POST" action="">
    <div class="form-group">
        <label for="senha_inicial">Senha Inicial * (mín. 6 caracteres)</label>
        <input type="text" id="senha_inicial" name="senha_inicial" required minlength="6" 
            placeholder="Digite uma senha inicial para o aluno"
            value="<?php echo isset($_POST['senha_inicial']) ? htmlspecialchars($_POST['senha_inicial']) : ''; ?>">
        <small style="color: var(--gray-text); display: block; margin-top: 5px;">
            💡 <strong>Dica:</strong> Use senhas simples como "123456", "primeiroAcesso" ou "bemvindo2024". O aluno poderá alterá-la depois no sistema.
        </small>
    </div>
    
    <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 15px; margin: 20px 0;">
        <p style="color: #92400e; margin-bottom: 10px;">
            ⚠️ <strong>Importante:</strong> Anote as credenciais abaixo e envie para o aluno:
        </p>
        <div style="background: white; padding: 12px; border-radius: 6px; font-family: monospace;">
            <strong>Email:</strong> <?php echo htmlspecialchars($aluno['email']); ?><br>
            <strong>Senha:</strong> <span id="senha-preview">(será exibida após digitar)</span>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">✅ Criar Acesso ao Sistema</button>
        <a href="editar.php?id=<?php echo $aluno_id; ?>" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<script>
document.getElementById('senha_inicial').addEventListener('input', function() {
    const senhaPreview = document.getElementById('senha-preview');
    senhaPreview.textContent = this.value || '(será exibida após digitar)';
});
</script>

<?php include '../includes/footer.php'; ?>