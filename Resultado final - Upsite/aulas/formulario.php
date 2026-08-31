<?php
require_once __DIR__ . '/../includes/functions.php';
exigirPerfil(['coordenacao']);

$pdo = getConexao();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$registro = ['turma_id' => '', 'sala_id' => '', 'instrutor_id' => '', 'data_aula' => '', 'hora_inicio' => '', 'hora_fim' => '', 'status' => 'Agendada', 'observacoes' => ''];
$erro = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM aulas WHERE id = ?');
    $stmt->execute([$id]);
    $registro = $stmt->fetch();
    if (!$registro) {
        header('Location: listar.php');
        exit;
    }
}

$turmas = $pdo->query("SELECT id, nome, instrutor_id FROM turmas WHERE ativo=1 ORDER BY nome")->fetchAll();
$salas = $pdo->query("SELECT id, nome, capacidade FROM salas WHERE ativo=1 ORDER BY nome")->fetchAll();
$instrutores = $pdo->query("SELECT id, nome FROM instrutores WHERE ativo=1 ORDER BY nome")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turmaId = (int)($_POST['turma_id'] ?? 0);
    $salaId = (int)($_POST['sala_id'] ?? 0);
    $instrutorId = (int)($_POST['instrutor_id'] ?? 0);
    $dataAula = $_POST['data_aula'] ?? '';
    $horaInicio = $_POST['hora_inicio'] ?? '';
    $horaFim = $_POST['hora_fim'] ?? '';
    $status = $_POST['status'] ?? 'Agendada';
    $observacoes = trim($_POST['observacoes'] ?? '');

    if (!$turmaId || !$salaId || !$instrutorId || !$dataAula || !$horaInicio || !$horaFim) {
        $erro = 'Todos os campos, exceto observações, são obrigatórios.';
    } elseif ($horaFim <= $horaInicio) {
        $erro = 'O horário de término deve ser depois do horário de início.';
    } else {
        // ---------------------------------------------------------------
        // PARTE CRÍTICA: checagem de conflito + gravação dentro da MESMA
        // transação, para que duas requisições simultâneas não consigam
        // "passar" pela checagem ao mesmo tempo e gerar choque de horário.
        // ---------------------------------------------------------------
        try {
            $pdo->beginTransaction();

            $conflito = verificarConflitoAula($pdo, $salaId, $instrutorId, $dataAula, $horaInicio, $horaFim, $id);

            if ($conflito) {
                $pdo->rollBack();
                $erro = $conflito;
            } else {
                if ($id) {
                    $stmt = $pdo->prepare('UPDATE aulas SET turma_id=?, sala_id=?, instrutor_id=?, data_aula=?, hora_inicio=?, hora_fim=?, status=?, observacoes=? WHERE id=?');
                    $stmt->execute([$turmaId, $salaId, $instrutorId, $dataAula, $horaInicio, $horaFim, $status, $observacoes, $id]);
                    $msg = 'Aula atualizada com sucesso.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO aulas (turma_id, sala_id, instrutor_id, data_aula, hora_inicio, hora_fim, status, observacoes) VALUES (?,?,?,?,?,?,?,?)');
                    $stmt->execute([$turmaId, $salaId, $instrutorId, $dataAula, $horaInicio, $horaFim, $status, $observacoes]);
                    $msg = 'Aula cadastrada com sucesso.';
                }
                $pdo->commit();
                header('Location: listar.php?sucesso=' . urlencode($msg));
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $erro = 'Erro ao salvar: ' . $e->getMessage();
        }
    }

    $registro = [
        'turma_id' => $turmaId, 'sala_id' => $salaId, 'instrutor_id' => $instrutorId,
        'data_aula' => $dataAula, 'hora_inicio' => $horaInicio, 'hora_fim' => $horaFim,
        'status' => $status, 'observacoes' => $observacoes,
    ];
}

$tituloPagina = $id ? 'Editar Aula' : 'Nova Aula';
require __DIR__ . '/../includes/header.php';
?>

<h2 class="page-title"><?= e($tituloPagina) ?></h2>

<?php if ($erro): ?><div class="alert alert-danger"><strong>⚠️</strong> <?= e($erro) ?></div><?php endif; ?>

<div class="form-card" style="max-width:680px">
  <form method="post" class="precisa-validacao" novalidate>
    <div class="mb-3">
      <label class="form-label">Turma *</label>
      <select name="turma_id" class="form-select" required>
        <option value="">Selecione...</option>
        <?php foreach ($turmas as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= (string)$registro['turma_id'] === (string)$t['id'] ? 'selected' : '' ?>><?= e($t['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Sala *</label>
        <select name="sala_id" class="form-select" required>
          <option value="">Selecione...</option>
          <?php foreach ($salas as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= (string)$registro['sala_id'] === (string)$s['id'] ? 'selected' : '' ?>>
              <?= e($s['nome']) ?> (<?= (int)$s['capacidade'] ?> lugares)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Instrutor *</label>
        <select name="instrutor_id" class="form-select" required>
          <option value="">Selecione...</option>
          <?php foreach ($instrutores as $i): ?>
            <option value="<?= (int)$i['id'] ?>" <?= (string)$registro['instrutor_id'] === (string)$i['id'] ? 'selected' : '' ?>><?= e($i['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Data *</label>
        <input type="date" name="data_aula" class="form-control" required value="<?= e($registro['data_aula']) ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Hora início *</label>
        <input type="time" id="hora_inicio" name="hora_inicio" class="form-control" required value="<?= e($registro['hora_inicio']) ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Hora fim *</label>
        <input type="time" id="hora_fim" name="hora_fim" class="form-control" required value="<?= e($registro['hora_fim']) ?>">
      </div>
    </div>
    <div id="aviso-horario" class="alert alert-warning py-2 d-none">O horário de término deve ser depois do horário de início.</div>

    <?php if ($id): ?>
    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <?php foreach (['Agendada', 'Realizada', 'Cancelada'] as $s): ?>
          <option value="<?= $s ?>" <?= $registro['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <div class="mb-3">
      <label class="form-label">Observações</label>
      <textarea name="observacoes" class="form-control" rows="2"><?= e($registro['observacoes']) ?></textarea>
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="listar.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
