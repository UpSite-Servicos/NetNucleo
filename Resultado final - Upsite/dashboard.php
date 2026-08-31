<?php
require_once __DIR__ . '/includes/functions.php';
exigirLogin();

$pdo = getConexao();
$perfil = $_SESSION['usuario_perfil'];
$refId = $_SESSION['usuario_referencia_id'];
$tituloPagina = 'Painel';

// Monta os dados do painel de acordo com o perfil logado
if ($perfil === 'coordenacao') {
    $totais = [
        'Instrutores' => $pdo->query("SELECT COUNT(*) FROM instrutores WHERE ativo=1")->fetchColumn(),
        'Alunos' => $pdo->query("SELECT COUNT(*) FROM alunos WHERE ativo=1")->fetchColumn(),
        'Turmas' => $pdo->query("SELECT COUNT(*) FROM turmas WHERE ativo=1")->fetchColumn(),
        'Salas' => $pdo->query("SELECT COUNT(*) FROM salas WHERE ativo=1")->fetchColumn(),
    ];

    $sqlProximas = "SELECT a.*, t.nome AS turma_nome, s.nome AS sala_nome, i.nome AS instrutor_nome
                     FROM aulas a
                     JOIN turmas t ON t.id = a.turma_id
                     JOIN salas s ON s.id = a.sala_id
                     JOIN instrutores i ON i.id = a.instrutor_id
                     WHERE a.data_aula >= CURDATE() AND a.status = 'Agendada'
                     ORDER BY a.data_aula ASC, a.hora_inicio ASC
                     LIMIT 8";
    $proximasAulas = $pdo->query($sqlProximas)->fetchAll();

} elseif ($perfil === 'instrutor') {
    $totais = [
        'Minhas Turmas' => $pdo->prepare("SELECT COUNT(*) FROM turmas WHERE instrutor_id = ? AND ativo=1"),
    ];
    $totais['Minhas Turmas']->execute([$refId]);
    $totais['Minhas Turmas'] = $totais['Minhas Turmas']->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM aulas WHERE instrutor_id = ? AND data_aula >= CURDATE() AND status='Agendada'");
    $stmt->execute([$refId]);
    $totais['Próximas Aulas'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT a.*, t.nome AS turma_nome, s.nome AS sala_nome, i.nome AS instrutor_nome
                            FROM aulas a
                            JOIN turmas t ON t.id = a.turma_id
                            JOIN salas s ON s.id = a.sala_id
                            JOIN instrutores i ON i.id = a.instrutor_id
                            WHERE a.instrutor_id = ? AND a.data_aula >= CURDATE() AND a.status = 'Agendada'
                            ORDER BY a.data_aula ASC, a.hora_inicio ASC LIMIT 8");
    $stmt->execute([$refId]);
    $proximasAulas = $stmt->fetchAll();

} else { // aluno
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM matriculas WHERE aluno_id = ?");
    $stmt->execute([$refId]);
    $totais = ['Minhas Turmas' => $stmt->fetchColumn()];

    $stmt = $pdo->prepare("SELECT a.*, t.nome AS turma_nome, s.nome AS sala_nome, i.nome AS instrutor_nome
                            FROM aulas a
                            JOIN turmas t ON t.id = a.turma_id
                            JOIN salas s ON s.id = a.sala_id
                            JOIN instrutores i ON i.id = a.instrutor_id
                            JOIN matriculas m ON m.turma_id = t.id
                            WHERE m.aluno_id = ? AND a.data_aula >= CURDATE() AND a.status = 'Agendada'
                            ORDER BY a.data_aula ASC, a.hora_inicio ASC LIMIT 8");
    $stmt->execute([$refId]);
    $proximasAulas = $stmt->fetchAll();
}

$cores = ['bg-cor-1', 'bg-cor-2', 'bg-cor-3', 'bg-cor-4'];
require __DIR__ . '/includes/header.php';
?>

<h2 class="page-title">Olá, <?= e($_SESSION['usuario_nome']) ?> 👋</h2>
<p class="text-muted mb-4">Perfil: <span class="text-capitalize fw-semibold"><?= e($perfil) ?></span></p>

<div class="row g-3 mb-4">
  <?php $i = 0; foreach ($totais as $rotulo => $valor): ?>
    <div class="col-6 col-md-3">
      <div class="card-resumo <?= $cores[$i % count($cores)] ?>">
        <div class="valor"><?= (int)$valor ?></div>
        <div class="rotulo"><?= e($rotulo) ?></div>
      </div>
    </div>
  <?php $i++; endforeach; ?>
</div>

<div class="form-card">
  <h5 class="mb-3">Próximas aulas agendadas</h5>
  <?php if (empty($proximasAulas)): ?>
    <p class="text-muted mb-0">Nenhuma aula futura agendada.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-sisged table-hover align-middle">
      <thead>
        <tr>
          <th>Data</th><th>Horário</th><th>Turma</th><th>Sala</th><th>Instrutor</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($proximasAulas as $aula): ?>
          <tr>
            <td><?= formatarData($aula['data_aula']) ?></td>
            <td><?= formatarHora($aula['hora_inicio']) ?> - <?= formatarHora($aula['hora_fim']) ?></td>
            <td><?= e($aula['turma_nome']) ?></td>
            <td><?= e($aula['sala_nome']) ?></td>
            <td><?= e($aula['instrutor_nome']) ?></td>
            <td><span class="badge badge-status-<?= e($aula['status']) ?>"><?= e($aula['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
