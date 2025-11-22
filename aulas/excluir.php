<?php
$nivel = 1;
include '../includes/header.php';

if (!isProprietario()) {
    definirMensagem('error', 'Acesso negado! Apenas proprietários podem excluir aulas.');
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID da aula não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Buscar informações da aula
$sql = "SELECT a.*, m.nome as modalidade, p.nome_professor as professor 
        FROM aulas a
        JOIN modalidades m ON a.modalidade_id = m.id_modalidade
        JOIN professores p ON a.professor_id = p.id_professor
        WHERE a.id_aula = ?";
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

// Excluir aula (CASCADE irá excluir horários e matrículas)
$sql = "DELETE FROM aulas WHERE id_aula = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    definirMensagem('success', 'Aula excluída com sucesso! (' . $aula['modalidade'] . ' - ' . $aula['professor'] . ')');
} else {
    definirMensagem('error', 'Erro ao excluir aula: ' . $stmt->error);
}

$stmt->close();
header('Location: index.php');
exit;
?>