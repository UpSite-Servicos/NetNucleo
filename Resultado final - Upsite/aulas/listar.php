<?php
require_once __DIR__ . '/../includes/functions.php';
exigirLogin();

$pdo = getConexao();
$perfil = $_SESSION['usuario_perfil'];
$refId = $_SESSION['usuario_referencia_id'];
$dataFiltro = trim($_GET['data'] ?? '');

$sqlBase = "SELECT a.*, t.nome AS turma_nome, s.nome AS sala_nome, i.nome AS instrutor_nome
            FROM aulas a
            JOIN turmas t ON t.id = a.turma_id
            JOIN salas s ON s.id = a.sala_id
            JOIN instrutores i ON i.id = a.instrutor_id
            WHERE 1=1";
$params = [];

if ($perfil === 'instrutor') {
    $sqlBase .= " AND a.instrutor_id = :ref";
    $params[':ref'] = $refId;
} elseif ($perfil === 'aluno') {
    $sqlBase .= " AND a.turma_id IN (SELECT turma_id FROM matriculas WHERE aluno_id = :ref)";
    $params[':ref'] = $refId;
}

if ($dataFiltro !== '') {
    $sqlBase .= " AND a.data_aula = :data";
    $params[':data'] = $dataFiltro;
}

$sqlBase .= " ORDER BY a.data_aula DESC, a.hora_inicio ASC";
$stmt = $pdo->prepare($sqlBase);
$stmt->execute($params);
$aulas = $stmt->fetchAll();

$tituloPagina = 'Aulas';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <h2 class="page-title mb-0"><?= $perfil === 'coordenacao' ? 'Aulas' : 'Minhas Aulas' ?></h2>
  <?php if ($perfil === 'coordenacao'): ?>
    <a href="formulario.php" class="btn btn-primary">+ Nova Aula</a>
  <?php endif; ?>
</div>

<?php if (isset($_GET['sucesso'])): ?>
  <div class="alert alert-success alert-auto-fechar"><?= e($_GET['sucesso']) ?></div>
<?php endif; ?>

<div class="form-card mb-3">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label small mb-1">Filtrar por data</label>
      <input type="date" name="data" class="form-control" value="<?= e($dataFiltro) ?>">
    </div>
    <div class="col-md-3">
      <button class="btn btn-outline-secondary w-100" type="submit">Filtrar</button>
    </div>
    <?php if ($dataFiltro): ?>
      <div class="col-md-3"><a href="listar.php" class="btn btn-link">Limpar filtro</a></div>
    <?php endif; ?>
  </form>
</div>

<div class="form-card">
  <div class="table-responsive">
    <table class="table table-sisged table-hover align-middle">
      <thead>
        <tr>
          <th>Data</th><th>Horário</th><th>Turma</th><th>Sala</th><th>Instrutor</th><th>Status</th>
          <?php if ($perfil === 'coordenacao'): ?><th class="text-end">Ações</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($aulas)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma aula encontrada.</td></tr>
        <?php endif; ?>
        <?php foreach ($aulas as $a): ?>
          <tr>
            <td><?= formatarData($a['data_aula']) ?></td>
            <td><?= formatarHora($a['hora_inicio']) ?> - <?= formatarHora($a['hora_fim']) ?></td>
            <td><?= e($a['turma_nome']) ?></td>
            <td><?= e($a['sala_nome']) ?></td>
            <td><?= e($a['instrutor_nome']) ?></td>
            <td><span class="badge badge-status-<?= e($a['status']) ?>"><?= e($a['status']) ?></span></td>
            <?php if ($perfil === 'coordenacao'): ?>
            <td class="text-end">
              <a href="formulario.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
              <form action="excluir.php" method="post" class="d-inline">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Excluir esta aula? Essa ação não pode ser desfeita.">Excluir</button>
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
