<?php
require_once __DIR__ . '/includes/functions.php';

header('Location: ' . caminhoBase() . (estaLogado() ? '/dashboard.php' : '/auth/login.php'));
exit;
