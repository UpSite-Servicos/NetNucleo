<?php
require_once __DIR__ . '/../includes/functions.php';
exigirPerfil(['coordenacao']);

$pdo = getConexao();
$busca = trim($_GET['busca'] ?? '');

$sql = "SELECT * FROM instrutores WHERE 1=1";
$params = [];
if ($busca !== '') {
    $sql .= " AND (nome LIKE :b OR email LIKE :b OR especialidade LIKE :b)";
    $params[':b'] = "%$busca%";
}
$sql .= " ORDER BY nome ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$instrutores = $stmt->fetchAll();

$tituloPagina = 'Instrutores';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <h2 class="page-title mb-0">Instrutores</h2>
  <a href="formulario.php" class="btn btn-primary">+ Novo Instrutor</a>
</div>

<?php if (isset($_GET['sucesso'])): ?>
  <div class="alert alert-success alert-auto-fechar"><?= e($_GET['sucesso']) ?></div>
<?php endif; ?>

<div class="form-card mb-3">
  <form method="get" class="row g-2">
    <div class="col-md-8">
      <input type="text" name="busca" class="form-control" placeholder="Buscar por nome, e-mail ou especialidade..." value="<?= e($busca) ?>">
    </div>
    <div class="col-md-4">
      <button class="btn btn-outline-secondary w-100" type="submit">Buscar</button>
    </div>
  </form>
</div>

<div class="form-card">
  <div class="table-responsive">
    <table class="table table-sisged table-hover align-middle">
      <thead>
        <tr><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Especialidade</th><th class="text-end">Ações</th></tr>
      </thead>
      <tbody>
        <?php if (empty($instrutores)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Nenhum instrutor encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($instrutores as $i): ?>
          <tr>
            <td><?= e($i['nome']) ?></td>
            <td><?= e($i['email']) ?></td>
            <td><?= e($i['telefone']) ?></td>
            <td><?= e($i['especialidade']) ?></td>
            <td class="text-end">
              <a href="formulario.php?id=<?= (int)$i['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
              <form action="excluir.php" method="post" class="d-inline">
                <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Excluir o instrutor '<?= e($i['nome']) ?>'? Essa ação não pode ser desfeita.">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
