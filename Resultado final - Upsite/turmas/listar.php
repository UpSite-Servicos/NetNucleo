<?php
require_once __DIR__ . '/../includes/functions.php';
exigirLogin();

$pdo = getConexao();
$perfil = $_SESSION['usuario_perfil'];
$refId = $_SESSION['usuario_referencia_id'];
$busca = trim($_GET['busca'] ?? '');

if ($perfil === 'coordenacao') {
    $sql = "SELECT t.*, i.nome AS instrutor_nome,
                   (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id) AS total_alunos
            FROM turmas t
            LEFT JOIN instrutores i ON i.id = t.instrutor_id
            WHERE 1=1";
    $params = [];
    if ($busca !== '') {
        $sql .= " AND (t.nome LIKE :b OR t.disciplina LIKE :b)";
        $params[':b'] = "%$busca%";
    }
    $sql .= " ORDER BY t.nome ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

} elseif ($perfil === 'instrutor') {
    $stmt = $pdo->prepare("SELECT t.*, i.nome AS instrutor_nome,
                                   (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id) AS total_alunos
                            FROM turmas t
                            LEFT JOIN instrutores i ON i.id = t.instrutor_id
                            WHERE t.instrutor_id = ?
                            ORDER BY t.nome ASC");
    $stmt->execute([$refId]);

} else { // aluno
    $stmt = $pdo->prepare("SELECT t.*, i.nome AS instrutor_nome,
                                   (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id) AS total_alunos
                            FROM turmas t
                            LEFT JOIN instrutores i ON i.id = t.instrutor_id
                            JOIN matriculas m2 ON m2.turma_id = t.id
                            WHERE m2.aluno_id = ?
                            ORDER BY t.nome ASC");
    $stmt->execute([$refId]);
}

$turmas = $stmt->fetchAll();

$tituloPagina = 'Turmas';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <h2 class="page-title mb-0"><?= $perfil === 'coordenacao' ? 'Turmas' : 'Minhas Turmas' ?></h2>
  <?php if ($perfil === 'coordenacao'): ?>
    <a href="formulario.php" class="btn btn-primary">+ Nova Turma</a>
  <?php endif; ?>
</div>

<?php if (isset($_GET['sucesso'])): ?>
  <div class="alert alert-success alert-auto-fechar"><?= e($_GET['sucesso']) ?></div>
<?php endif; ?>

<?php if ($perfil === 'coordenacao'): ?>
<div class="form-card mb-3">
  <form method="get" class="row g-2">
    <div class="col-md-8">
      <input type="text" name="busca" class="form-control" placeholder="Buscar por nome ou disciplina..." value="<?= e($busca) ?>">
    </div>
    <div class="col-md-4">
      <button class="btn btn-outline-secondary w-100" type="submit">Buscar</button>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="form-card">
  <div class="table-responsive">
    <table class="table table-sisged table-hover align-middle">
      <thead>
        <tr>
          <th>Nome</th><th>Disciplina</th><th>Instrutor</th><th>Turno</th><th>Período</th><th>Alunos</th>
          <?php if ($perfil === 'coordenacao'): ?><th class="text-end">Ações</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($turmas)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma turma encontrada.</td></tr>
        <?php endif; ?>
        <?php foreach ($turmas as $t): ?>
          <tr>
            <td><?= e($t['nome']) ?></td>
            <td><?= e($t['disciplina']) ?></td>
            <td><?= e($t['instrutor_nome'] ?? '—') ?></td>
            <td><?= e($t['turno']) ?></td>
            <td><?= formatarData($t['data_inicio']) ?> a <?= formatarData($t['data_fim']) ?></td>
            <td><?= (int)$t['total_alunos'] ?></td>
            <?php if ($perfil === 'coordenacao'): ?>
            <td class="text-end">
              <a href="formulario.php?id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
              <form action="excluir.php" method="post" class="d-inline">
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Excluir a turma '<?= e($t['nome']) ?>'? Essa ação não pode ser desfeita.">Excluir</button>
              </form>
            </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
