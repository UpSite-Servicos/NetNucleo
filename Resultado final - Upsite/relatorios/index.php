<?php
require_once __DIR__ . '/../includes/functions.php';
exigirPerfil(['coordenacao', 'instrutor']);

$pdo = getConexao();
$perfil = $_SESSION['usuario_perfil'];
$refId = $_SESSION['usuario_referencia_id'];

$dataInicio = trim($_GET['data_inicio'] ?? '');
$dataFim = trim($_GET['data_fim'] ?? '');
$salaId = (int)($_GET['sala_id'] ?? 0);
$instrutorId = (int)($_GET['instrutor_id'] ?? 0);
$turmaId = (int)($_GET['turma_id'] ?? 0);
$filtrado = $_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET);

$salas = $pdo->query("SELECT id, nome FROM salas ORDER BY nome")->fetchAll();
$turmas = $pdo->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll();
$instrutores = ($perfil === 'coordenacao')
    ? $pdo->query("SELECT id, nome FROM instrutores ORDER BY nome")->fetchAll()
    : [];

$sql = "SELECT a.*, t.nome AS turma_nome, t.disciplina, s.nome AS sala_nome, i.nome AS instrutor_nome
        FROM aulas a
        JOIN turmas t ON t.id = a.turma_id
        JOIN salas s ON s.id = a.sala_id
        JOIN instrutores i ON i.id = a.instrutor_id
        WHERE 1=1";
$params = [];

// Instrutor só pode ver relatório das próprias aulas
if ($perfil === 'instrutor') {
    $sql .= " AND a.instrutor_id = :ref";
    $params[':ref'] = $refId;
} elseif ($instrutorId) {
    $sql .= " AND a.instrutor_id = :instrutor_id";
    $params[':instrutor_id'] = $instrutorId;
}

if ($dataInicio) { $sql .= " AND a.data_aula >= :di"; $params[':di'] = $dataInicio; }
if ($dataFim) { $sql .= " AND a.data_aula <= :df"; $params[':df'] = $dataFim; }
if ($salaId) { $sql .= " AND a.sala_id = :sala_id"; $params[':sala_id'] = $salaId; }
if ($turmaId) { $sql .= " AND a.turma_id = :turma_id"; $params[':turma_id'] = $turmaId; }

$sql .= " ORDER BY a.data_aula ASC, a.hora_inicio ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resultado = $stmt->fetchAll();

$totalHoras = 0;
foreach ($resultado as $a) {
    $ini = strtotime($a['hora_inicio']);
    $fim = strtotime($a['hora_fim']);
    $totalHoras += ($fim - $ini) / 3600;
}

$tituloPagina = 'Relatórios';
require __DIR__ . '/../includes/header.php';
?>

<h2 class="page-title">Relatórios de Aulas</h2>

<div class="form-card mb-3 no-print">
  <form method="get" class="row g-3">
    <div class="col-md-3">
      <label class="form-label small mb-1">Data início</label>
      <input type="date" name="data_inicio" class="form-control" value="<?= e($dataInicio) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small mb-1">Data fim</label>
      <input type="date" name="data_fim" class="form-control" value="<?= e($dataFim) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small mb-1">Sala</label>
      <select name="sala_id" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($salas as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= $salaId === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($perfil === 'coordenacao'): ?>
    <div class="col-md-3">
      <label class="form-label small mb-1">Instrutor</label>
      <select name="instrutor_id" class="form-select">
        <option value="">Todos</option>
        <?php foreach ($instrutores as $i): ?>
          <option value="<?= (int)$i['id'] ?>" <?= $instrutorId === (int)$i['id'] ? 'selected' : '' ?>><?= e($i['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="col-md-4">
      <label class="form-label small mb-1">Turma</label>
      <select name="turma_id" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($turmas as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= $turmaId === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end gap-2">
      <button type="submit" class="btn btn-primary w-100">Gerar relatório</button>
    </div>
    <?php if ($filtrado): ?>
    <div class="col-md-3 d-flex align-items-end">
      <button type="button" onclick="window.print()" class="btn btn-outline-secondary w-100">🖨️ Imprimir / PDF</button>
    </div>
    <?php endif; ?>
  </form>
</div>

<?php if ($filtrado): ?>
<div class="form-card">
  <div class="d-flex justify-content-between flex-wrap mb-3">
    <h5>Resultado: <?= count($resultado) ?> aula(s)</h5>
    <h5>Carga horária total: <?= number_format($totalHoras, 1, ',', '.') ?> h</h5>
  </div>
  <div class="table-responsive">
    <table class="table table-sisged table-hover align-middle">
      <thead>
        <tr><th>Data</th><th>Horário</th><th>Turma</th><th>Disciplina</th><th>Sala</th><th>Instrutor</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php if (empty($resultado)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma aula encontrada para os filtros selecionados.</td></tr>
        <?php endif; ?>
        <?php foreach ($resultado as $a): ?>
          <tr>
            <td><?= formatarData($a['data_aula']) ?></td>
            <td><?= formatarHora($a['hora_inicio']) ?> - <?= formatarHora($a['hora_fim']) ?></td>
            <td><?= e($a['turma_nome']) ?></td>
            <td><?= e($a['disciplina'] ?? '') ?></td>
            <td><?= e($a['sala_nome']) ?></td>
            <td><?= e($a['instrutor_nome']) ?></td>
            <td><span class="badge badge-status-<?= e($a['status']) ?>"><?= e($a['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
  <div class="alert alert-info">Escolha os filtros acima e clique em "Gerar relatório".</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
