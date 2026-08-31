<?php
require_once __DIR__ . '/../includes/functions.php';
exigirPerfil(['coordenacao']);

$pdo = getConexao();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$registro = ['nome' => '', 'email' => '', 'telefone' => '', 'especialidade' => ''];
$erro = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM instrutores WHERE id = ?');
    $stmt->execute([$id]);
    $registro = $stmt->fetch();
    if (!$registro) {
        header('Location: listar.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $especialidade = trim($_POST['especialidade'] ?? '');

    if ($nome === '' || $email === '') {
        $erro = 'Nome e e-mail são obrigatórios.';
    } else {
        try {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE instrutores SET nome=?, email=?, telefone=?, especialidade=? WHERE id=?');
                $stmt->execute([$nome, $email, $telefone, $especialidade, $id]);
                $msg = 'Instrutor atualizado com sucesso.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO instrutores (nome, email, telefone, especialidade) VALUES (?,?,?,?)');
                $stmt->execute([$nome, $email, $telefone, $especialidade]);
                $msg = 'Instrutor cadastrado com sucesso.';
            }
            header('Location: listar.php?sucesso=' . urlencode($msg));
            exit;
        } catch (PDOException $e) {
            $erro = ($e->getCode() === '23000')
                ? 'Já existe um instrutor cadastrado com esse e-mail.'
                : 'Erro ao salvar: ' . $e->getMessage();
        }
    }
    $registro = compact('nome', 'email', 'telefone', 'especialidade');
}

$tituloPagina = $id ? 'Editar Instrutor' : 'Novo Instrutor';
require __DIR__ . '/../includes/header.php';
?>

<h2 class="page-title"><?= e($tituloPagina) ?></h2>

<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<div class="form-card" style="max-width:600px">
  <form method="post" class="precisa-validacao" novalidate>
    <div class="mb-3">
      <label class="form-label">Nome completo *</label>
      <input type="text" name="nome" class="form-control" required value="<?= e($registro['nome']) ?>">
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
      <label class="form-label">Especialidade / Área</label>
      <input type="text" name="especialidade" class="form-control" value="<?= e($registro['especialidade']) ?>">
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="listar.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
