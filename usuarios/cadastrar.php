<?php
$titulo = "Cadastrar Usuário";
$nivel = 1;
include '../includes/header.php';

if (!isProprietario()) {
    definirMensagem('error', 'Acesso negado!');
    header('Location: ../index.php');
    exit;
}

// Buscar professores e alunos para vínculo
$professores = $conn->query("SELECT id_professor, nome_professor FROM professores WHERE ativo = 1 ORDER BY nome_professor");
$alunos = $conn->query("SELECT id_aluno, nome FROM alunos WHERE ativo = 1 ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizarDados($conn, $_POST['nome']);
    $email = sanitizarDados($conn, $_POST['email']);
    $senha = $_POST['senha'];
    $senha_confirma = $_POST['senha_confirma'];
    $tipo_usuario = sanitizarDados($conn, $_POST['tipo_usuario']);
    $vinculo_id = !empty($_POST['vinculo_id']) ? (int)$_POST['vinculo_id'] : NULL;
    
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório";
    }
    
    if (empty($email) || !validarEmail($email)) {
        $erros[] = "Email inválido";
    } else {
        // Verificar email duplicado
        $sql = "SELECT id_usuario FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erros[] = "Este email já está cadastrado";
        }
        $stmt->close();
    }
    
    if (empty($senha) || strlen($senha) < 6) {
        $erros[] = "A senha deve ter no mínimo 6 caracteres";
    }
    
    if ($senha !== $senha_confirma) {
        $erros[] = "As senhas não coincidem";
    }
    
    if (empty($erros)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, vinculo_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $nome, $email, $senha_hash, $tipo_usuario, $vinculo_id);
        
        if ($stmt->execute()) {
            definirMensagem('success', 'Usuário cadastrado com sucesso!');
            header('Location: index.php');
            exit;
        } else {
            $erros[] = "Erro ao cadastrar usuário";
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
    <h2>Cadastrar Novo Usuário</h2>
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
            <label for="senha">Senha * (mín. 6 caracteres)</label>
            <input type="password" id="senha" name="senha" required minlength="6">
        </div>
        
        <div class="form-group">
            <label for="senha_confirma">Confirmar Senha *</label>
            <input type="password" id="senha_confirma" name="senha_confirma" required minlength="6">
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="tipo_usuario">Tipo de Usuário *</label>
            <select id="tipo_usuario" name="tipo_usuario" required onchange="mostrarVinculo()">
                <option value="">Selecione...</option>
                <option value="proprietario" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == 'proprietario') ? 'selected' : ''; ?>>Proprietário</option>
                <option value="professor" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == 'professor') ? 'selected' : ''; ?>>Professor</option>
                <option value="aluno" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == 'aluno') ? 'selected' : ''; ?>>Aluno</option>
            </select>
        </div>
        
        <div class="form-group" id="campo-vinculo" style="display: none;">
            <label for="vinculo_id" id="label-vinculo">Vincular a</label>
            <select id="vinculo_id" name="vinculo_id">
                <option value="">Sem vínculo</option>
            </select>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Cadastrar</button>
        <a href="index.php" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<script>
const professores = <?php echo json_encode($professores->fetch_all(MYSQLI_ASSOC)); ?>;
const alunos = <?php echo json_encode($alunos->fetch_all(MYSQLI_ASSOC)); ?>;

function mostrarVinculo() {
    const tipo = document.getElementById('tipo_usuario').value;
    const campoVinculo = document.getElementById('campo-vinculo');
    const selectVinculo = document.getElementById('vinculo_id');
    const labelVinculo = document.getElementById('label-vinculo');
    
    selectVinculo.innerHTML = '<option value="">Sem vínculo</option>';
    
    if (tipo === 'professor') {
        campoVinculo.style.display = 'block';
        labelVinculo.textContent = 'Vincular ao Professor';
        professores.forEach(prof => {
            selectVinculo.innerHTML += `<option value="${prof.id_professor}">${prof.nome_professor}</option>`;
        });
    } else if (tipo === 'aluno') {
        campoVinculo.style.display = 'block';
        labelVinculo.textContent = 'Vincular ao Aluno';
        alunos.forEach(aluno => {
            selectVinculo.innerHTML += `<option value="${aluno.id_aluno}">${aluno.nome}</option>`;
        });
    } else {
        campoVinculo.style.display = 'none';
    }
}

// Carregar na inicialização
mostrarVinculo();
</script>

<?php include '../includes/footer.php'; ?>