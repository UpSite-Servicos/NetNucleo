<?php
/**
 * Configuração de conexão com o banco de dados.
 * Padrão do WAMP: usuário "root", sem senha, porta 3306.
 * Se o seu MySQL/phpMyAdmin usar outra senha, ajuste DB_PASS abaixo.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'sisged');
define('DB_USER', 'root');
define('DB_PASS', '');

function getConexao(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die('Erro ao conectar ao banco de dados. Verifique se o MySQL do WAMP está ativo e se o banco "sisged" foi importado. Detalhe técnico: ' . $e->getMessage());
        }
    }

    return $pdo;
}
