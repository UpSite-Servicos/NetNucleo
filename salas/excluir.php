<?php
require_once __DIR__ . '/../includes/functions.php';
exigirPerfil(['coordenacao']);

$pdo = getConexao();
$id = (int)($_POST['id'] ?? 0);

if ($id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM salas WHERE id = ?');
        $stmt->execute([$id]);
        $msg = 'Sala excluída com sucesso.';
    } catch (PDOException $e) {
        $msg = ($e->getCode() === '23000')
            ? 'Não é possível excluir: esta sala está vinculada a aulas cadastradas. Remova as aulas antes de excluir a sala.'
            : 'Erro ao excluir: ' . $e->getMessage();
    }
}

header('Location: listar.php?sucesso=' . urlencode($msg ?? 'Operação concluída.'));
exit;
