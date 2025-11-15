<?php
$nivel = 1;
include '../includes/header.php';

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID do aluno não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Verificar se aluno existe
$sql = "SELECT nome FROM alunos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    definirMensagem('error', 'Aluno não encontrado');
    header('Location: index.php');
    exit;
}

$aluno = $result->fetch_assoc();
$stmt->close();

// Excluir aluno (CASCADE irá excluir as matrículas também)
$sql = "DELETE FROM alunos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    definirMensagem('success', 'Aluno "' . $aluno['nome'] . '" excluído com sucesso!');
} else {
    definirMensagem('error', 'Erro ao excluir aluno: ' . $stmt->error);
}

$stmt->close();
header('Location: index.php');
exit;
?>