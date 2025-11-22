<?php
$titulo = "Cadastrar Aluno";
$nivel = 1;
include '../includes/header.php';

if (!isProprietario()) {
    definirMensagem('error', 'Acesso negado! Apenas proprietários podem gerenciar modalidades.');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizarDados($conn, $_POST['nome']);
    $email = sanitizarDados($conn, $_POST['email']);
    $telefone = sanitizarDados($conn, $_POST['telefone']);
    $data_nascimento = sanitizarDados($conn, $_POST['data_nascimento']);
    
    // Dados do endereço
    $endereco = sanitizarDados($conn, $_POST['endereco']);
    $bairro = sanitizarDados($conn, $_POST['bairro']);
    $cep = sanitizarDados($conn, $_POST['cep']);
    $numero = sanitizarDados($conn, $_POST['numero']);
    
    // Criar usuário?
    $criar_usuario = isset($_POST['criar_usuario']) ? true : false;
    $senha_inicial = $_POST['senha_inicial'];
    
    $erros = [];
    
    // Validações
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório";
    }
    
    if (empty($email)) {
        $erros[] = "O email é obrigatório";
    } elseif (!validarEmail($email)) {
        $erros[] = "Email inválido";
    } else {
        // Verificar se email já existe
        $sql = "SELECT id_aluno FROM alunos WHERE email = ?";
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
            $id_endereco = null;
            
            // 1. Inserir endereço (se preenchido)
            if (!empty($endereco) || !empty($bairro) || !empty($cep) || !empty($numero)) {
                $sql = "INSERT INTO enderecos (endereco, bairro, cep, numero) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $endereco, $bairro, $cep, $numero);
                $stmt->execute();
                
                $id_endereco = $conn->insert_id;
                $stmt->close();
            }
            
            // 2. Inserir aluno
            $sql = "INSERT INTO alunos (nome, email, telefone, data_nascimento, id_endereco) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $nome, $email, $telefone, $data_nascimento, $id_endereco);
            $stmt->execute();
            
            $aluno_id = $conn->insert_id;
            $stmt->close();
            
            // 3. Criar usuário se solicitado
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
    <div class="section">
        <h3 class="section-title">Dados Pessoais</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="nome">Nome Completo *</label>
                <input type="text" id="nome" name="nome" required 
                       value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000" 
                       value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="data_nascimento">Data de Nascimento</label>
                <input type="date" id="data_nascimento" name="data_nascimento" 
                       value="<?php echo isset($_POST['data_nascimento']) ? $_POST['data_nascimento'] : ''; ?>">
            </div>
        </div>
    </div>
    
    <div class="section">
        <h3 class="section-title">Endereço</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="cep">CEP</label>
                <input type="text" id="cep" name="cep" placeholder="00000-000" 
                       value="<?php echo isset($_POST['cep']) ? htmlspecialchars($_POST['cep']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="bairro">Bairro</label>
                <input type="text" id="bairro" name="bairro" 
                       value="<?php echo isset($_POST['bairro']) ? htmlspecialchars($_POST['bairro']) : ''; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group" style="flex: 3;">
                <label for="endereco">Endereço</label>
                <input type="text" id="endereco" name="endereco" placeholder="Rua, Avenida, etc" 
                       value="<?php echo isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : ''; ?>">
            </div>
            
            <div class="form-group" style="flex: 1;">
                <label for="numero">Número</label>
                <input type="text" id="numero" name="numero" 
                       value="<?php echo isset($_POST['numero']) ? htmlspecialchars($_POST['numero']) : ''; ?>">
            </div>
        </div>
    </div>
    
    <div class="section" style="background: #dbeafe; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6;">
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
    
    <script>
    function toggleSenha() {
        const checkbox = document.getElementById('criar_usuario');
        const campoSenha = document.getElementById('campo-senha');
        campoSenha.style.display = checkbox.checked ? 'block' : 'none';
    }
    </script>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Cadastrar</button>
        <a href="index.php" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>