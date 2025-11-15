<?php
$nivel = 1;
include '../includes/header.php';

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID do professor não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Verificar se professor existe
$sql = "SELECT nome FROM professores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    definirMensagem('error', 'Professor não encontrado');
    header('Location: index.php');
    exit;
}

$professor = $result->fetch_assoc();
$stmt->close();

// Excluir professor (CASCADE irá excluir vínculos e aulas)
$sql = "DELETE FROM professores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    definirMensagem('success', 'Professor "' . $professor['nome'] . '" excluído com sucesso!');
} else {
    definirMensagem('error', 'Erro ao excluir professor: ' . $stmt->error);
}

$stmt->close();
header('Location: index.php');
exit;
?>