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
$professores = $conn->query("SELECT id, nome FROM professores WHERE ativo = 1 ORDER BY nome");
$alunos = $conn->query("SELECT id, nome FROM alunos WHERE ativo = 1 ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizarDados($conn, $_POST['nome']);
    $email = sanitizarDados($conn, $_POST['email']);
    $telefone = sanitizarDados($conn, $_POST['telefone']);
    $data_nascimento = sanitizarDados($conn, $_POST['data_nascimento']);
    $endereco = sanitizarDados($conn, $_POST['endereco']);
    $criar_usuario = isset($_POST['criar_usuario']) ? true : false;
    $senha_inicial = $_POST['senha_inicial'];
    
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
    
    // Validar senha se for criar usuário
    if ($criar_usuario) {
        if (empty($senha_inicial)) {
            $erros[] = "A senha inicial é obrigatória para criar acesso ao sistema";
        } elseif (strlen($senha_inicial) < 6) {
            $erros[] = "A senha deve ter no mínimo 6 caracteres";
        }
    }
    
    if (empty($erros)) {
        // Iniciar transação
        $conn->begin_transaction();
        
        try {
            // 1. Inserir aluno
            $sql = "INSERT INTO alunos (nome, email, telefone, data_nascimento, endereco) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $nome, $email, $telefone, $data_nascimento, $endereco);
            $stmt->execute();
            
            $aluno_id = $conn->insert_id;
            $stmt->close();
            
            // 2. Criar usuário se solicitado
            if ($criar_usuario) {
                $senha_hash = password_hash($senha_inicial, PASSWORD_DEFAULT);
                $tipo_usuario = 'aluno';
                
                $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, vinculo_id) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $nome, $email, $senha_hash, $tipo_usuario, $aluno_id);
                $stmt->execute();
                $stmt->close();
                
                $mensagem_sucesso = "Aluno cadastrado com sucesso! Usuário criado com email: {$email} e senha: {$senha_inicial}";
            } else {
                $mensagem_sucesso = "Aluno cadastrado com sucesso!";
            }
            
            $conn->commit();
            definirMensagem('success', $mensagem_sucesso);
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $erros[] = "Erro ao cadastrar aluno: " . $e->getMessage();
        }
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

    <div class="section" style="background: #dbeafe; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; margin-top: 20px;">
        <h3 style="color: #1e40af; margin-bottom: 15px;">🔐 Acesso ao Sistema</h3>
        <p style="color: #1e40af; margin-bottom: 15px;">
            Marque a opção abaixo para criar automaticamente um usuário para este aluno acessar o sistema e visualizar suas aulas.
        </p>
        
        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" name="criar_usuario" id="criar_usuario" onchange="toggleSenha()" 
                    <?php echo (isset($_POST['criar_usuario'])) ? 'checked' : ''; ?>>
                <strong>Criar acesso ao sistema para este aluno</strong>
            </label>
        </div>
        
        <div class="form-group" id="campo-senha" style="display: <?php echo (isset($_POST['criar_usuario'])) ? 'block' : 'none'; ?>;">
            <label for="senha_inicial">Senha Inicial * (mín. 6 caracteres)</label>
            <input type="text" id="senha_inicial" name="senha_inicial" minlength="6" 
                placeholder="Digite uma senha inicial para o aluno"
                value="<?php echo isset($_POST['senha_inicial']) ? htmlspecialchars($_POST['senha_inicial']) : ''; ?>">
            <small style="color: #1e40af; display: block; margin-top: 5px;">
                💡 Dica: Use uma senha simples como "123456" ou "primeiroAcesso". O aluno poderá alterá-la depois.
            </small>
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
            selectVinculo.innerHTML += `<option value="${prof.id}">${prof.nome}</option>`;
        });
    } else if (tipo === 'aluno') {
        campoVinculo.style.display = 'block';
        labelVinculo.textContent = 'Vincular ao Aluno';
        alunos.forEach(aluno => {
            selectVinculo.innerHTML += `<option value="${aluno.id}">${aluno.nome}</option>`;
        });
    } else {
        campoVinculo.style.display = 'none';
    }
}

function toggleSenha() {
    const checkbox = document.getElementById('criar_usuario');
    const campoSenha = document.getElementById('campo-senha');
    campoSenha.style.display = checkbox.checked ? 'block' : 'none';
}

// Carregar na inicialização
mostrarVinculo();
</script>

<?php include '../includes/footer.php'; ?>