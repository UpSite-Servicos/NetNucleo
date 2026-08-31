<?php
/**
 * Roda UMA VEZ, pelo navegador, depois de importar sisged.sql:
 *   http://localhost/sisged/sql/seed_usuarios.php
 *
 * Cria os 3 usuários de exemplo com senha "123456" (hash gerado
 * corretamente pelo PHP, com password_hash()).
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getConexao();

$senhaPadrao = password_hash('123456', PASSWORD_DEFAULT);

$usuarios = [
    ['Coordenação SISGED', 'coordenacao@sisged.com.br', 'coordenacao', null],
    ['Carlos Andrade', 'carlos.andrade@sisged.com.br', 'instrutor', 1],
    ['Ana Beatriz Santos', 'ana.santos@aluno.sisged.com.br', 'aluno', 1],
];

$stmt = $pdo->prepare(
    'INSERT INTO usuarios (nome, email, senha_hash, perfil, referencia_id)
     VALUES (:nome, :email, :senha, :perfil, :ref)
     ON DUPLICATE KEY UPDATE senha_hash = VALUES(senha_hash)'
);

$criados = [];
foreach ($usuarios as [$nome, $email, $perfil, $ref]) {
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha' => $senhaPadrao,
        ':perfil' => $perfil,
        ':ref' => $ref,
    ]);
    $criados[] = $email;
}

echo '<h2>SISGED - seed de usuários concluído</h2>';
echo '<p>Usuários criados/atualizados (senha para todos: <strong>123456</strong>):</p><ul>';
foreach ($criados as $email) {
    echo '<li>' . htmlspecialchars($email) . '</li>';
}
echo '</ul><p><a href="../auth/login.php">Ir para o login</a></p>';
echo '<p style="color:#b00">Por segurança, apague este arquivo (seed_usuarios.php) depois de usar.</p>';
