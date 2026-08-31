<?php
require_once __DIR__ . '/../includes/functions.php';
exigirPerfil(['coordenacao']);

$pdo = getConexao();
$id = (int)($_POST['id'] ?? 0);

if ($id) {
    try {
        // matriculas tem ON DELETE CASCADE; aulas tem ON DELETE CASCADE em turma_id
        $stmt = $pdo->prepare('DELETE FROM turmas WHERE id = ?');
        $stmt->execute([$id]);
        $msg = 'Turma excluída com sucesso (matrículas e aulas vinculadas também foram removidas).';
    } catch (PDOException $e) {
        $msg = 'Erro ao excluir: ' . $e->getMessage();
    }
}

header('Location: listar.php?sucesso=' . urlencode($msg ?? 'Operação concluída.'));
exit;
