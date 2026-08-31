<?php
require_once __DIR__ . '/../includes/functions.php';
exigirPerfil(['coordenacao']);

$pdo = getConexao();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$registro = ['nome' => '', 'capacidade' => '', 'localizacao' => '', 'recursos' => ''];
$erro = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM salas WHERE id = ?');
    $stmt->execute([$id]);
    $registro = $stmt->fetch();
    if (!$registro) {
        header('Location: listar.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $capacidade = (int)($_POST['capacidade'] ?? 0);
    $localizacao = trim($_POST['localizacao'] ?? '');
    $recursos = trim($_POST['recursos'] ?? '');

    if ($nome === '' || $capacidade <= 0) {
        $erro = 'Nome e capacidade (maior que zero) são obrigatórios.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE salas SET nome=?, capacidade=?, localizacao=?, recursos=? WHERE id=?');
            $stmt->execute([$nome, $capacidade, $localizacao, $recursos, $id]);
            $msg = 'Sala atualizada com sucesso.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO salas (nome, capacidade, localizacao, recursos) VALUES (?,?,?,?)');
            $stmt->execute([$nome, $capacidade, $localizacao, $recursos]);
            $msg = 'Sala cadastrada com sucesso.';
        }
        header('Location: listar.php?sucesso=' . urlencode($msg));
        exit;
    }
    $registro = compact('nome', 'capacidade', 'localizacao', 'recursos');
}

$tituloPagina = $id ? 'Editar Sala' : 'Nova Sala';
require __DIR__ . '/../includes/header.php';
?>

<h2 class="page-title"><?= e($tituloPagina) ?></h2>

<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<div class="form-card" style="max-width:600px">
  <form method="post" class="precisa-validacao" novalidate>
    <div class="mb-3">
      <label class="form-label">Nome da sala *</label>
      <input type="text" name="nome" class="form-control" required value="<?= e($registro['nome']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Capacidade (nº de pessoas) *</label>
      <input type="number" min="1" name="capacidade" class="form-control" required value="<?= e((string)$registro['capacidade']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Localização</label>
      <input type="text" name="localizacao" class="form-control" placeholder="Ex: Bloco A - 1º andar" value="<?= e($registro['localizacao']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Recursos disponíveis</label>
      <input type="text" name="recursos" class="form-control" placeholder="Ex: Projetor, ar-condicionado" value="<?= e($registro['recursos']) ?>">
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="listar.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
