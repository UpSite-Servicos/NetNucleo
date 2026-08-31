<?php
require_once __DIR__ . '/../includes/functions.php';
exigirPerfil(['coordenacao']);

$pdo = getConexao();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$registro = ['nome' => '', 'email' => '', 'matricula' => '', 'telefone' => ''];
$erro = null;
$turmasDoAluno = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM alunos WHERE id = ?');
    $stmt->execute([$id]);
    $registro = $stmt->fetch();
    if (!$registro) {
        header('Location: listar.php');
        exit;
    }
    $stmt = $pdo->prepare('SELECT turma_id FROM matriculas WHERE aluno_id = ?');
    $stmt->execute([$id]);
    $turmasDoAluno = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$todasTurmas = $pdo->query("SELECT id, nome FROM turmas WHERE ativo=1 ORDER BY nome")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $turmasSelecionadas = array_map('intval', $_POST['turmas'] ?? []);

    if ($nome === '' || $email === '' || $matricula === '') {
        $erro = 'Nome, e-mail e matrícula são obrigatórios.';
    } else {
        try {
            $pdo->beginTransaction();

            if ($id) {
                $stmt = $pdo->prepare('UPDATE alunos SET nome=?, email=?, matricula=?, telefone=? WHERE id=?');
                $stmt->execute([$nome, $email, $matricula, $telefone, $id]);
                $alunoId = $id;
            } else {
                $stmt = $pdo->prepare('INSERT INTO alunos (nome, email, matricula, telefone) VALUES (?,?,?,?)');
                $stmt->execute([$nome, $email, $matricula, $telefone]);
                $alunoId = (int)$pdo->lastInsertId();
            }

            // Ressincroniza matrículas: remove as que foram desmarcadas, adiciona as novas
            $pdo->prepare('DELETE FROM matriculas WHERE aluno_id = ?')->execute([$alunoId]);
            if (!empty($turmasSelecionadas)) {
                $stmtMat = $pdo->prepare('INSERT INTO matriculas (aluno_id, turma_id) VALUES (?, ?)');
                foreach ($turmasSelecionadas as $turmaId) {
                    $stmtMat->execute([$alunoId, $turmaId]);
                }
            }

            $pdo->commit();
            $msg = $id ? 'Aluno atualizado com sucesso.' : 'Aluno cadastrado com sucesso.';
            header('Location: listar.php?sucesso=' . urlencode($msg));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = ($e->getCode() === '23000')
                ? 'Já existe um aluno cadastrado com esse e-mail ou matrícula.'
                : 'Erro ao salvar: ' . $e->getMessage();
        }
    }
    $registro = compact('nome', 'email', 'matricula', 'telefone');
    $turmasDoAluno = $turmasSelecionadas;
}

$tituloPagina = $id ? 'Editar Aluno' : 'Novo Aluno';
require __DIR__ . '/../includes/header.php';
?>

<h2 class="page-title"><?= e($tituloPagina) ?></h2>

<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<div class="form-card" style="max-width:640px">
  <form method="post" class="precisa-validacao" novalidate>
    <div class="row">
      <div class="col-md-8 mb-3">
        <label class="form-label">Nome completo *</label>
        <input type="text" name="nome" class="form-control" required value="<?= e($registro['nome']) ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Matrícula *</label>
        <input type="text" name="matricula" class="form-control" required value="<?= e($registro['matricula']) ?>">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">E-mail *</label>
      <input type="email" name="email" class="form-control" required value="<?= e($registro['email']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Telefone</label>
      <input type="text" name="telefone" class="form-control" value="<?= e($registro['telefone']) ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Turmas matriculadas</label>
      <?php if (empty($todasTurmas)): ?>
        <p class="text-muted small">Nenhuma turma cadastrada ainda.</p>
      <?php else: ?>
        <div class="row">
          <?php foreach ($todasTurmas as $t): ?>
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="turmas[]" value="<?= (int)$t['id'] ?>"
                       id="turma<?= (int)$t['id'] ?>" <?= in_array($t['id'], $turmasDoAluno) ? 'checked' : '' ?>>
                <label class="form-check-label" for="turma<?= (int)$t['id'] ?>"><?= e($t['nome']) ?></label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="listar.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
