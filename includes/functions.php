<?php
session_start();

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function registerUser($conn, $email, $password) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Email já cadastrado'];
    }
    
    $passwordHash = hashPassword($password);
    
    $stmt = $conn->prepare("INSERT INTO users (email, password, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $email, $passwordHash);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Usuário registrado com sucesso'];
    } else {
        return ['success' => false, 'message' => 'Erro ao registrar usuário'];
    }
}

function loginUser($conn, $email, $password) {
    $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Email ou senha inválidos'];
    }
    
    $user = $result->fetch_assoc();
    
    if (!verifyPassword($password, $user['password'])) {
        return ['success' => false, 'message' => 'Email ou senha inválidos'];
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    
    return ['success' => true, 'message' => 'Login realizado com sucesso'];
}

function logoutUser() {
    session_unset();
    session_destroy();
    return ['success' => true, 'message' => 'Logout realizado'];
}

function isAuthenticated() {
    return isset($_SESSION['user_id']);
}
?>