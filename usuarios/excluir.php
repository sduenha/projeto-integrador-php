<?php
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

// Impedir exclusão do próprio usuário
if ($id == $_SESSION['usuario_id']) {
    definirMensagem('error', 'Você não pode excluir sua própria conta!');
    header('Location: index.php');
    exit;
}

$sql = "SELECT nome FROM usuarios WHERE id = ?";
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

$sql = "DELETE FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    definirMensagem('success', 'Usuário "' . $usuario['nome'] . '" excluído com sucesso!');
} else {
    definirMensagem('error', 'Erro ao excluir usuário');
}

$stmt->close();
header('Location: index.php');
exit;
?>