<?php
require_once __DIR__ . '/../includes/functions.php';
exigirPerfil(['coordenacao']);

$pdo = getConexao();
$id = (int)($_POST['id'] ?? 0);

if ($id) {
    // matriculas tem ON DELETE CASCADE, então excluir o aluno já remove as matrículas dele
    $stmt = $pdo->prepare('DELETE FROM alunos WHERE id = ?');
    $stmt->execute([$id]);
    $msg = 'Aluno excluído com sucesso.';
}

header('Location: listar.php?sucesso=' . urlencode($msg ?? 'Operação concluída.'));
exit;
