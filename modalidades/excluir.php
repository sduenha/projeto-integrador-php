<?php
$nivel = 1;
include '../includes/header.php';

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID da modalidade não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Verificar se modalidade existe
$sql = "SELECT nome FROM modalidades WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    definirMensagem('error', 'Modalidade não encontrada');
    header('Location: index.php');
    exit;
}

$modalidade = $result->fetch_assoc();
$stmt->close();

// Excluir modalidade (CASCADE irá excluir aulas relacionadas)
$sql = "DELETE FROM modalidades WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    definirMensagem('success', 'Modalidade "' . $modalidade['nome'] . '" excluída com sucesso!');
} else {
    definirMensagem('error', 'Erro ao excluir modalidade: ' . $stmt->error);
}

$stmt->close();
header('Location: index.php');
exit;
?>