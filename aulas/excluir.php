<?php
$nivel = 1;
include '../includes/header.php';

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID da aula não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Buscar informações da aula
$sql = "SELECT a.*, m.nome as modalidade, p.nome as professor 
        FROM aulas a
        JOIN modalidades m ON a.modalidade_id = m.id
        JOIN professores p ON a.professor_id = p.id
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    definirMensagem('error', 'Aula não encontrada');
    header('Location: index.php');
    exit;
}

$aula = $result->fetch_assoc();
$stmt->close();

// Excluir aula (CASCADE irá excluir matrículas)
$sql = "DELETE FROM aulas WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    definirMensagem('success', 'Aula excluída com sucesso! (' . $aula['modalidade'] . ' - ' . $aula['professor'] . ' - ' . $aula['dia_semana'] . ')');
} else {
    definirMensagem('error', 'Erro ao excluir aula: ' . $stmt->error);
}

$stmt->close();
header('Location: index.php');
exit;
?>