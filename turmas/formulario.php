<?php
require_once __DIR__ . '/../includes/functions.php';
exigirPerfil(['coordenacao']);

$pdo = getConexao();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$registro = ['nome' => '', 'disciplina' => '', 'instrutor_id' => '', 'data_inicio' => '', 'data_fim' => '', 'turno' => 'Manhã'];
$erro = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM turmas WHERE id = ?');
    $stmt->execute([$id]);
    $registro = $stmt->fetch();
    if (!$registro) {
        header('Location: listar.php');
        exit;
    }
}

$instrutores = $pdo->query("SELECT id, nome FROM instrutores WHERE ativo=1 ORDER BY nome")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $disciplina = trim($_POST['disciplina'] ?? '');
    $instrutorId = (int)($_POST['instrutor_id'] ?? 0);
    $dataInicio = $_POST['data_inicio'] ?? '';
    $dataFim = $_POST['data_fim'] ?? '';
    $turno = $_POST['turno'] ?? 'Manhã';

    if ($nome === '' || $disciplina === '' || !$instrutorId) {
        $erro = 'Nome, disciplina e instrutor são obrigatórios.';
    } elseif ($dataInicio && $dataFim && $dataFim < $dataInicio) {
        $erro = 'A data final não pode ser anterior à data de início.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE turmas SET nome=?, disciplina=?, instrutor_id=?, data_inicio=?, data_fim=?, turno=? WHERE id=?');
            $stmt->execute([$nome, $disciplina, $instrutorId, $dataInicio ?: null, $dataFim ?: null, $turno, $id]);
            $msg = 'Turma atualizada com sucesso.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO turmas (nome, disciplina, instrutor_id, data_inicio, data_fim, turno) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$nome, $disciplina, $instrutorId, $dataInicio ?: null, $dataFim ?: null, $turno]);
            $msg = 'Turma cadastrada com sucesso.';
        }
        header('Location: listar.php?sucesso=' . urlencode($msg));
        exit;
    }
    $registro = compact('nome', 'disciplina', 'turno') + ['instrutor_id' => $instrutorId, 'data_inicio' => $dataInicio, 'data_fim' => $dataFim];
}

$tituloPagina = $id ? 'Editar Turma' : 'Nova Turma';
require __DIR__ . '/../includes/header.php';
?>

<h2 class="page-title"><?= e($tituloPagina) ?></h2>

<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<div class="form-card" style="max-width:640px">
  <form method="post" class="precisa-validacao" novalidate>
    <div class="mb-3">
      <label class="form-label">Nome da turma *</label>
      <input type="text" name="nome" class="form-control" required value="<?= e($registro['nome']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Disciplina *</label>
      <input type="text" name="disciplina" class="form-control" required value="<?= e($registro['disciplina']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Instrutor responsável *</label>
      <select name="instrutor_id" class="form-select" required>
        <option value="">Selecione...</option>
        <?php foreach ($instrutores as $i): ?>
          <option value="<?= (int)$i['id'] ?>" <?= (string)$registro['instrutor_id'] === (string)$i['id'] ? 'selected' : '' ?>>
            <?= e($i['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Data início</label>
        <input type="date" name="data_inicio" class="form-control" value="<?= e($registro['data_inicio']) ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Data fim</label>
        <input type="date" name="data_fim" class="form-control" value="<?= e($registro['data_fim']) ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Turno</label>
        <select name="turno" class="form-select">
          <?php foreach (['Manhã', 'Tarde', 'Noite'] as $t): ?>
            <option value="<?= $t ?>" <?= $registro['turno'] === $t ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="listar.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
