<?php
// Espera que $tituloPagina já esteja definido pela página que incluiu este arquivo.
$perfil = $_SESSION['usuario_perfil'] ?? null;
$base = caminhoBase();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($tituloPagina ?? 'SISGED') ?> - SISGED</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>

<?php if (estaLogado()): ?>
<nav class="navbar navbar-dark sisged-topbar px-3">
  <button class="btn btn-sm btn-outline-light d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sisgedSidebar">
    ☰
  </button>
  <a class="navbar-brand fw-bold" href="<?= $base ?>/dashboard.php">SISGED</a>
  <div class="ms-auto d-flex align-items-center text-white gap-3">
    <span class="d-none d-sm-inline small"><?= e($_SESSION['usuario_nome']) ?> · <span class="badge bg-light text-dark text-capitalize"><?= e($perfil) ?></span></span>
    <a href="<?= $base ?>/auth/logout.php" class="btn btn-sm btn-outline-light">Sair</a>
  </div>
</nav>

<div class="d-flex">
  <div class="offcanvas-md offcanvas-start sisged-sidebar" tabindex="-1" id="sisgedSidebar">
    <div class="offcanvas-body p-0">
      <ul class="nav flex-column pt-3">
        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/dashboard.php">📊 Painel</a></li>

        <?php if ($perfil === 'coordenacao'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>/instrutores/listar.php">👤 Instrutores</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>/alunos/listar.php">🎓 Alunos</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>/salas/listar.php">🏫 Salas</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>/turmas/listar.php">📚 Turmas</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>/aulas/listar.php">🗓️ Aulas</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>/relatorios/index.php">📄 Relatórios</a></li>
        <?php elseif ($perfil === 'instrutor'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>/turmas/listar.php">📚 Minhas Turmas</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>/aulas/listar.php">🗓️ Minhas Aulas</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>/relatorios/index.php">📄 Relatórios</a></li>
        <?php elseif ($perfil === 'aluno'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>/turmas/listar.php">📚 Minhas Turmas</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>/aulas/listar.php">🗓️ Minhas Aulas</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <main class="flex-grow-1 p-3 p-md-4 sisged-content">
<?php endif; ?>
