<?php
require_once 'config/database.php';

$conn = conectarBanco();

echo "<h2>🔧 Criar/Atualizar Usuário Administrador</h2>";

$nome = "Administrador";
$email = "admin@sistema.com";
$senha = "admin123";
$tipo = "proprietario";

// Gerar hash correto
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// Verificar se já existe
$sql = "SELECT id FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Atualizar
    $usuario = $result->fetch_assoc();
    $id = $usuario['id'];
    
    $sql = "UPDATE usuarios SET nome = ?, senha = ?, tipo_usuario = ?, ativo = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $nome, $senha_hash, $tipo, $id);
    
    if ($stmt->execute()) {
        echo "<p style='color: green; font-weight: bold;'>✅ Usuário ATUALIZADO com sucesso!</p>";
    } else {
        echo "<p style='color: red;'>❌ Erro ao atualizar: " . $stmt->error . "</p>";
    }
} else {
    // Criar novo
    $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo) VALUES (?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nome, $email, $senha_hash, $tipo);
    
    if ($stmt->execute()) {
        echo "<p style='color: green; font-weight: bold;'>✅ Usuário CRIADO com sucesso!</p>";
    } else {
        echo "<p style='color: red;'>❌ Erro ao criar: " . $stmt->error . "</p>";
    }
}

$stmt->close();

// Testar login
echo "<hr>";
echo "<h3>🧪 Teste de Login</h3>";

$sql = "SELECT * FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
    
    echo "<p><strong>Usuário encontrado:</strong></p>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> {$usuario['id']}</li>";
    echo "<li><strong>Nome:</strong> {$usuario['nome']}</li>";
    echo "<li><strong>Email:</strong> {$usuario['email']}</li>";
    echo "<li><strong>Tipo:</strong> {$usuario['tipo_usuario']}</li>";
    echo "<li><strong>Ativo:</strong> " . ($usuario['ativo'] ? 'Sim' : 'Não') . "</li>";
    echo "</ul>";
    
    echo "<p><strong>Hash da senha no banco:</strong></p>";
    echo "<textarea style='width:100%; height:80px; font-family:monospace;'>{$usuario['senha']}</textarea>";
    
    // Testar verificação
    $verifica = password_verify($senha, $usuario['senha']);
    echo "<p><strong>Teste de verificação de senha:</strong> ";
    
    if ($verifica) {
        echo "<span style='color: green; font-weight: bold;'>✅ SENHA CORRETA - Login funcionará!</span>";
    } else {
        echo "<span style='color: red; font-weight: bold;'>❌ SENHA INCORRETA - Login falhará!</span>";
    }
    echo "</p>";
    
} else {
    echo "<p style='color: red;'>❌ Usuário não encontrado no banco!</p>";
}

$stmt->close();
$conn->close();

echo "<hr>";
echo "<h3>📋 Credenciais para Login:</h3>";
echo "<div style='background: #dbeafe; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6;'>";
echo "<p><strong>Email:</strong> <code>{$email}</code></p>";
echo "<p><strong>Senha:</strong> <code>{$senha}</code></p>";
echo "</div>";

echo "<hr>";
echo "<p><a href='login.php' style='display: inline-block; background: #6366f1; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;'>🔐 Ir para Login</a></p>";
?>