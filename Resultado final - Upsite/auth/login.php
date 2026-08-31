<?php
require_once __DIR__ . '/../includes/functions.php';

if (estaLogado()) {
    header('Location: ' . caminhoBase() . '/dashboard.php');
    exit;
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $pdo = getConexao();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email AND ativo = 1');
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];
            $_SESSION['usuario_referencia_id'] = $usuario['referencia_id'];

            header('Location: ' . caminhoBase() . '/dashboard.php');
            exit;
        }

        $erro = 'E-mail ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - SISGED</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= caminhoBase() ?>/assets/css/style.css">
</head>
<body>
<div class="login-wrapper">
  <div class="login-card">
    <h1>SISGED</h1>
    <p class="subtitulo">Sistema de Gestão Educacional Dinâmico</p>

    <?php if ($erro): ?>
      <div class="alert alert-danger py-2"><?= e($erro) ?></div>
    <?php endif; ?>

    <form method="post" class="precisa-validacao" novalidate>
      <div class="mb-3">
        <label class="form-label">E-mail</label>
        <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Senha</label>
        <input type="password" name="senha" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Entrar</button>
    </form>

    <hr>
    <p class="small text-muted mb-1">Contas de exemplo (senha: <strong>123456</strong>):</p>
    <ul class="small text-muted mb-0 ps-3">
      <li>coordenacao@sisged.com.br</li>
      <li>carlos.andrade@sisged.com.br (instrutor)</li>
      <li>ana.santos@aluno.sisged.com.br (aluno)</li>
    </ul>
  </div>
</div>
<script src="<?= caminhoBase() ?>/assets/js/script.js"></script>
</body>
</html>
