<?php
require_once __DIR__ . '/../includes/functions.php';
exigirPerfil(['coordenacao']);

$pdo = getConexao();
$id = (int)($_POST['id'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare('DELETE FROM aulas WHERE id = ?');
    $stmt->execute([$id]);
    $msg = 'Aula excluída com sucesso.';
}

header('Location: listar.php?sucesso=' . urlencode($msg ?? 'Operação concluída.'));
exit;
