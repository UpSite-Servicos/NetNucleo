<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/** Retorna true se há um usuário logado. */
function estaLogado(): bool
{
    return isset($_SESSION['usuario_id']);
}

/** Exige login; se não houver, redireciona para a tela de login. */
function exigirLogin(): void
{
    if (!estaLogado()) {
        header('Location: ' . caminhoBase() . '/auth/login.php');
        exit;
    }
}

/**
 * Exige que o perfil logado esteja entre os permitidos.
 * Ex: exigirPerfil(['coordenacao']) ou exigirPerfil(['coordenacao','instrutor'])
 */
function exigirPerfil(array $perfisPermitidos): void
{
    exigirLogin();
    if (!in_array($_SESSION['usuario_perfil'], $perfisPermitidos, true)) {
        http_response_code(403);
        exit('<div style="font-family:sans-serif;padding:40px;text-align:center">
                <h2>Acesso negado</h2>
                <p>Seu perfil (' . htmlspecialchars($_SESSION['usuario_perfil']) . ') não tem permissão para acessar esta página.</p>
                <a href="' . caminhoBase() . '/dashboard.php">Voltar ao painel</a>
              </div>');
    }
}

/** Caminho base do projeto (funciona em qualquer subpasta do WAMP). */
function caminhoBase(): string
{
    return '/sisged';
}

/** Escapa texto para saída segura em HTML. */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** Formata data ISO (YYYY-MM-DD) para dd/mm/aaaa. */
function formatarData(?string $data): string
{
    if (!$data) return '-';
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d ? $d->format('d/m/Y') : $data;
}

/** Formata hora HH:MM:SS para HH:MM. */
function formatarHora(?string $hora): string
{
    if (!$hora) return '-';
    return substr($hora, 0, 5);
}

/**
 * PARTE CRÍTICA: verifica se existe conflito de horário para uma sala
 * ou para um instrutor em uma data, considerando SOBREPOSIÇÃO de
 * intervalos (não apenas horário idêntico).
 *
 * Duas aulas conflitam quando: inicio_A < fim_B  E  fim_A > inicio_B
 *
 * Deve ser chamada DENTRO de uma transação (ver aulas/salvar.php) para
 * evitar condição de corrida entre a checagem e a inserção.
 *
 * @return string|null Mensagem de conflito, ou null se não há conflito.
 */
function verificarConflitoAula(
    PDO $pdo,
    int $salaId,
    int $instrutorId,
    string $data,
    string $horaInicio,
    string $horaFim,
    ?int $ignorarAulaId = null
): ?string {
    $sql = "SELECT a.id, a.hora_inicio, a.hora_fim, s.nome AS sala_nome, i.nome AS instrutor_nome,
                   (a.sala_id = :sala_id) AS conflita_sala,
                   (a.instrutor_id = :instrutor_id) AS conflita_instrutor
            FROM aulas a
            JOIN salas s ON s.id = a.sala_id
            JOIN instrutores i ON i.id = a.instrutor_id
            WHERE a.data_aula = :data
              AND a.status != 'Cancelada'
              AND (a.sala_id = :sala_id OR a.instrutor_id = :instrutor_id)
              AND a.hora_inicio < :hora_fim
              AND a.hora_fim > :hora_inicio";

    $params = [
        ':sala_id' => $salaId,
        ':instrutor_id' => $instrutorId,
        ':data' => $data,
        ':hora_inicio' => $horaInicio,
        ':hora_fim' => $horaFim,
    ];

    if ($ignorarAulaId !== null) {
        $sql .= ' AND a.id != :ignorar_id';
        $params[':ignorar_id'] = $ignorarAulaId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $conflito = $stmt->fetch();

    if (!$conflito) {
        return null;
    }

    $periodo = formatarHora($conflito['hora_inicio']) . ' - ' . formatarHora($conflito['hora_fim']);

    if ($conflito['conflita_sala']) {
        return "Conflito: a sala \"{$conflito['sala_nome']}\" já tem uma aula agendada nesse horário ({$periodo}).";
    }

    return "Conflito: o(a) instrutor(a) \"{$conflito['instrutor_nome']}\" já tem uma aula agendada nesse horário ({$periodo}).";
}
