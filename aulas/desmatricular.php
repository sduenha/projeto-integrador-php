<?php
$nivel = 1;
include '../includes/header.php';

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID da matrícula não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];
$aula_id = isset($_GET['aula_id']) ? (int)$_GET['aula_id'] : null;

// Buscar informações da matrícula
$sql = "SELECT m.*, a.nome as aluno_nome
        FROM matriculas m
        JOIN alunos a ON m.aluno_id = a.id
        WHERE m.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    definirMensagem('error', 'Matrícula não encontrada');
    header('Location: index.php');
    exit;
}

$matricula = $result->fetch_assoc();
$stmt->close();

// Excluir matrícula (soft delete ou hard delete - aqui usando hard delete)
$sql = "DELETE FROM matriculas WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    definirMensagem('success', 'Aluno "' . $matricula['aluno_nome'] . '" desmatriculado com sucesso!');
} else {
    definirMensagem('error', 'Erro ao desmatricular aluno: ' . $stmt->error);
}

$stmt->close();

// Redirecionar de volta
if ($aula_id) {
    header('Location: matricular.php?aula_id=' . $aula_id);
} else {
    header('Location: index.php');
}
exit;
?>