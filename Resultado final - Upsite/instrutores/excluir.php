<?php
require_once __DIR__ . '/../includes/functions.php';
exigirPerfil(['coordenacao']);

$pdo = getConexao();
$id = (int)($_POST['id'] ?? 0);

if ($id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM instrutores WHERE id = ?');
        $stmt->execute([$id]);
        $msg = 'Instrutor excluído com sucesso.';
    } catch (PDOException $e) {
        // Erro 23000 = violação de chave estrangeira (instrutor tem turmas/aulas vinculadas)
        $msg = ($e->getCode() === '23000')
            ? 'Não é possível excluir: este instrutor está vinculado a turmas ou aulas. Remova ou reatribua essas turmas/aulas antes de excluir.'
            : 'Erro ao excluir: ' . $e->getMessage();
    }
}

header('Location: listar.php?sucesso=' . urlencode($msg ?? 'Operação concluída.'));
exit;
