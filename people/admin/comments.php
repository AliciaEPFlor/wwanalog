<?php
/* WWAnalog — Gerenciamento legado e moderação de comentários. */ require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$page_title = 'Moderação de comentários';
$active = 'admin';
require __DIR__ . '/../../includes/header.php';
check_csrf();
$d = data_read();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['comment_id'] ?? 0);
    foreach ($d['comments'] as &$c) if ((int)($c['id'] ?? 0) === $id) {
        $c['status'] = $_POST['status'] ?? 'published';
        $msg = 'Status do comentário atualizado.';
        break;
    }
    unset($c);
    if ($msg) {
        data_write($d);
        audit_event('moderacao_comentario', 'Comentário ' . $id . ' => ' . ($_POST['status'] ?? ''));
    }
    $d = data_read();
}
$q = trim($_GET['q'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$filterWork = (string)($_GET['work_id'] ?? '');
$filteredComments = array_values(array_filter($d['comments'], function ($c) use ($d, $q, $filterStatus, $filterWork) {
    $w = find_work($c['work_id'] ?? 0);
    if ($q !== '' && stripos(($c['user_name'] ?? '') . ' ' . ($c['body'] ?? '') . ' ' . ($w['title'] ?? ''), $q) === false) return false;
    if ($filterStatus !== '' && ($c['status'] ?? 'published') !== $filterStatus) return false;
    if ($filterWork !== '' && (string)($c['work_id'] ?? '') !== $filterWork) return false;
    return true;
})); ?><h1>Moderação de comentários</h1><?php if ($msg): ?><div class="notice success"><?= e($msg) ?></div><?php endif; ?><form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0"><input class="input" style="flex:1;min-width:190px" name="q" value="<?= e($q) ?>" placeholder="Buscar comentário, usuário ou obra"><select class="select" name="work_id">
        <option value="">Todas as obras</option><?php foreach ($d['works'] as $work): ?><option value="<?= e($work['id']) ?>" <?= $filterWork === (string)$work['id'] ? 'selected' : '' ?>><?= e($work['title']) ?></option><?php endforeach; ?>
    </select><select class="select" name="status">
        <option value="">Todos os status</option><?php foreach (['published' => 'Publicado', 'pending' => 'Aguardando análise', 'flagged' => 'Sinalizado', 'removed' => 'Removido'] as $key => $label): ?><option value="<?= e($key) ?>" <?= $filterStatus === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select><button class="btn">Filtrar</button><a class="btn ghost" href="<?= e(url('people/admin/comments.php')) ?>">Limpar</a></form>
<p class="small muted"><?= count($filteredComments) ?> comentário(s) encontrado(s).</p><?php foreach (array_reverse($filteredComments) as $c): $w = find_work($c['work_id'] ?? 0); ?><article class="panel" style="margin:12px 0">
        <div class="eyebrow"><?= e($c['user_name'] ?? 'Usuário') ?> · <?= e($w['title'] ?? 'Obra removida') ?> · <?= e($c['status'] ?? 'published') ?></div>
        <p><?= nl2br(e($c['body'] ?? '')) ?></p>
        <form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="comment_id" value="<?= e($c['id'] ?? 0) ?>"><select class="select" name="status">
                <option value="published">Publicado</option>
                <option value="pending">Aguardando análise</option>
                <option value="flagged">Sinalizado</option>
                <option value="removed">Removido</option>
            </select><button class="btn ghost">Atualizar status</button></form>
    </article><?php endforeach; ?><?php require __DIR__ . '/../../includes/footer.php'; ?>