<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'admin');
define('DB_PASS', '');
define('DB_NAME', 'gestao_aulas');

// Função para conectar ao banco
function conectarBanco() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Falha na conexão: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
        
    } catch (Exception $e) {
        die("Erro ao conectar ao banco de dados: " . $e->getMessage());
    }
}

// Função para sanitizar dados de entrada
function sanitizarDados($conn, $dados) {
    if (is_array($dados)) {
        return array_map(function($item) use ($conn) {
            return sanitizarDados($conn, $item);
        }, $dados);
    }
    return $conn->real_escape_string(trim($dados));
}

// Função para validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Função para formatar mensagens
function definirMensagem($tipo, $texto) {
    $_SESSION['mensagem'] = [
        'tipo' => $tipo, // success, error, warning, info
        'texto' => $texto
    ];
}

// Função para exibir mensagens
function exibirMensagem() {
    if (isset($_SESSION['mensagem'])) {
        $tipo = $_SESSION['mensagem']['tipo'];
        $texto = $_SESSION['mensagem']['texto'];
        
        $classes = [
            'success' => 'mensagem-sucesso',
            'error' => 'mensagem-erro',
            'warning' => 'mensagem-aviso',
            'info' => 'mensagem-info'
        ];
        
        $classe = isset($classes[$tipo]) ? $classes[$tipo] : 'mensagem-info';
        
        echo "<div class='mensagem {$classe}'>{$texto}</div>";
        unset($_SESSION['mensagem']);
    }
}

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== FUNÇÕES DE AUTENTICAÇÃO ====================

// Verificar se usuário está logado
function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . obterCaminhoLogin());
        exit;
    }
    
    // Atualizar último acesso
    if (isset($_SESSION['ultima_atualizacao']) && 
        (time() - $_SESSION['ultima_atualizacao'] > 300)) { // 5 minutos
        
        $conn = conectarBanco();
        $usuario_id = $_SESSION['usuario_id'];
        $sql = "UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        
        $_SESSION['ultima_atualizacao'] = time();
    }
}

// Obter caminho para login baseado no nível da pasta
function obterCaminhoLogin() {
    $nivel = isset($GLOBALS['nivel']) ? $GLOBALS['nivel'] : 0;
    return str_repeat('../', $nivel) . 'login.php';
}

// Obter caminho para index baseado no nível da pasta
function obterCaminhoIndex() {
    $nivel = isset($GLOBALS['nivel']) ? $GLOBALS['nivel'] : 0;
    return str_repeat('../', $nivel) . 'index.php';
}

// Fazer login
function fazerLogin($email, $senha) {
    $conn = conectarBanco();
    
    // Debug: registrar tentativa
    error_log("=== TENTATIVA DE LOGIN ===");
    error_log("Email: {$email}");
    error_log("Senha fornecida tem " . strlen($senha) . " caracteres");
    
    $sql = "SELECT * FROM usuarios WHERE email = ? AND ativo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    error_log("Usuários encontrados: " . $result->num_rows);
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        error_log("Hash no banco: " . $usuario['senha']);
        error_log("Tipo de usuário: " . $usuario['tipo_usuario']);
        
        // Verificar senha
        $senha_valida = password_verify($senha, $usuario['senha']);
        
        error_log("Resultado password_verify: " . ($senha_valida ? 'TRUE' : 'FALSE'));
        
        if ($senha_valida) {
            // Login bem-sucedido
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];
            $_SESSION['vinculo_id'] = $usuario['vinculo_id'];
            $_SESSION['ultima_atualizacao'] = time();
            
            error_log("LOGIN BEM-SUCEDIDO para usuário ID: " . $usuario['id']);
            
            // Atualizar último acesso
            $sql = "UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("i", $usuario['id']);
            $stmt2->execute();
            $stmt2->close();
            
            $stmt->close();
            $conn->close();
            return true;
        } else {
            error_log("SENHA INVÁLIDA");
        }
    } else {
        error_log("USUÁRIO NÃO ENCONTRADO OU INATIVO");
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

// Fazer logout
function fazerLogout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Verificar se é proprietário
function isProprietario() {
    return isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'proprietario';
}

// Verificar se é professor
function isProfessor() {
    return isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'professor';
}

// Verificar se é aluno
function isAluno() {
    return isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'aluno';
}

// Obter nome do tipo de usuário
function obterNomeTipoUsuario($tipo) {
    $tipos = [
        'proprietario' => 'Proprietário',
        'professor' => 'Professor',
        'aluno' => 'Aluno'
    ];
    return isset($tipos[$tipo]) ? $tipos[$tipo] : 'Usuário';
}
?>