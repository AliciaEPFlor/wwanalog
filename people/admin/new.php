<?php
/* WWAnalog — Formulário administrativo legado para cadastrar obras no armazenamento local. */ require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$page_title = 'Registrar obra';
$active = 'admin';
require __DIR__ . '/../../includes/header.php';
check_csrf();
$d = data_read();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($title === '' || !preg_match('/^[a-z0-9-]+$/', $slug))
        $err = 'Informe um título e um identificador simples (letras minúsculas, números e hífen).';
    elseif (find_work($slug))
        $err = 'Já existe uma obra com esse identificador.';
    else {
        $id = max(array_column($d['works'], 'id') ?: [0]) + 1;
        $d['works'][] = ['id' => $id, 'slug' => $slug, 'title' => $title, 'original_title' => trim($_POST['original_title'] ?? $title), 'category' => $_POST['category'] ?? 'foundfootage', 'tag' => '#' . preg_replace('/[^A-Za-z0-9]/', '', ucwords(str_replace('-', ' ', $slug))), 'description' => trim($_POST['description'] ?? ''), 'synopsis' => trim($_POST['synopsis'] ?? ''), 'story' => trim($_POST['story'] ?? ''), 'warning' => trim($_POST['warning'] ?? ''), 'rating' => trim($_POST['rating'] ?? ''), 'year' => trim($_POST['year'] ?? ''), 'status' => $_POST['status'] ?? 'Em andamento', 'creator' => trim($_POST['creator'] ?? ''), 'creator_url' => trim($_POST['creator_url'] ?? ''), 'image' => trim($_POST['image'] ?? ''), 'episodes' => []];
        data_write($d);
        audit_event('obra_criada', $title);
        header('Location: ' . url('people/admin/edit.php?id=' . $id));
        exit;
    }
} ?>
<div class="formbox formbox-wide">
    <h1>Registrar obra</h1>
    <?php if ($err): ?>
        <div class="notice"><?= e($err) ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <label>Título exibido</label>
        <input class="input" name="title" required>
        <label>Identificador (slug)</label>
        <input class="input" name="slug" placeholder="nome-da-obra" required>
        <label>Título original</label>
        <input class="input" name="original_title">
        <label>Categoria</label>
        <select class="select" name="category">
            <?php foreach ($d['categories'] as $c): ?>
                <option value="<?= e($c['slug']) ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
        <label>Criador</label>
        <input class="input" name="creator">
        <label>Link do canal</label>
        <input class="input" name="creator_url" type="url">
        <label>Ano</label>
        <input class="input" name="year"><label>Situação</label><select class="select" name="status">
            <option>Em andamento</option>
            <option>Concluída</option>
            <option>Cancelada</option>
        </select>
        <label>Classificação indicativa</label>
        <input class="input" name="rating" placeholder="Ex.: 14+ (não oficial) ">
        <label>Caminho da capa (ex.: assets/capa.jpg)</label>
        <input class="input" name="image">
        <label>Sinopse curta</label>
        <textarea class="textarea" name="description" rows="3"></textarea>
        <label>Sinopse completa</label>
        <textarea class="textarea" name="synopsis" rows="4"></textarea>
        <label>História da obra</label>
        <textarea class="textarea" name="story" rows="4"></textarea>
        <label>Avisos de conteúdo sensível</label>
        <textarea class="textarea" name="warning" rows="3"></textarea>
        <button class="btn">Registrar obra</button>
    </form>
</div><?php require __DIR__ . '/../../includes/footer.php'; ?>