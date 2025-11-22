<?php
$nivel = 1;
include '../includes/header.php';

// Permitir acesso para proprietário e professor
if (!isProprietario() && !isProfessor()) {
    definirMensagem('error', 'Acesso negado! Apenas proprietários e professores podem desmatricular alunos.');
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID da matrícula não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];
$aula_id = isset($_GET['aula_id']) ? (int)$_GET['aula_id'] : null;

// Buscar informações da matrícula
$sql = "SELECT m.*, a.nome as aluno_nome, au.professor_id
        FROM matriculas m
        JOIN alunos a ON m.aluno_id = a.id_aluno
        JOIN aulas au ON m.aula_id = au.id_aula
        WHERE m.id_matricula = ?";
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

// Se for professor, verificar se a aula é dele
if (isProfessor() && isset($_SESSION['vinculo_id'])) {
    $professor_id_logado = $_SESSION['vinculo_id'];
    
    if ($matricula['professor_id'] != $professor_id_logado) {
        definirMensagem('error', 'Você não tem permissão para desmatricular alunos desta aula');
        header('Location: index.php');
        exit;
    }
}

// Excluir matrícula
$sql = "DELETE FROM matriculas WHERE id_matricula = ?";
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