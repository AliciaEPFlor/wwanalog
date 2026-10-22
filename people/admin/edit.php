<!-- WWAnalog: Formulário administrativo legado para editar obras no armazenamento local. -->

<?php

require_once __DIR__ . '/../../includes/functions.php';

require_admin();

$page_title = 'Editar obra';
$active = 'admin';

// Carrega os dados
$d = data_read();

// Obtém o ID da obra
$id = (string) ($_GET['id'] ?? '');
$idx = null;

// Procura a obra
foreach (($d['works'] ?? []) as $i => $work) {
    if ((string) ($work['id'] ?? '') === $id && $id !== '') {
        $idx = $i;
        break;
    }
}

// Interrompe se a obra não existir
if ($idx === null) {
    http_response_code(404);
    exit('Obra não encontrada.');
}

$w = $d['works'][$idx];
$errors = [];

// Valores padrão do formulário
$fields = [
    'title',
    'original_title',
    'description',
    'synopsis',
    'story',
    'creator',
    'creator_url',
    'year',
    'rating',
    'warning',
    'image'
];

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Valida o token CSRF
    check_csrf();

    // Recolhe os campos enviados
    $updated = [];

    foreach ($fields as $field) {
        $updated[$field] = trim(
            (string) ($_POST[$field] ?? '')
        );
    }

    // Valida o título
    if ($updated['title'] === '') {
        $errors[] = 'O título da obra é obrigatório.';
    }

    // Valida o ano, permitindo campo vazio
    if ($updated['year'] !== '') {
        if (
            !ctype_digit($updated['year']) ||
            (int) $updated['year'] < 1880 ||
            (int) $updated['year'] > (int) date('Y') + 5
        ) {
            $errors[] = 'Informe um ano válido.';
        }
    }

    // Valida o link do criador
    if (
        $updated['creator_url'] !== '' &&
        (
            !filter_var(
                $updated['creator_url'],
                FILTER_VALIDATE_URL
            ) ||
            !in_array(
                strtolower(
                    (string) parse_url(
                        $updated['creator_url'],
                        PHP_URL_SCHEME
                    )
                ),
                ['http', 'https'],
                true
            )
        )
    ) {
        $errors[] = 'Informe um link válido usando HTTP ou HTTPS.';
    }

    // Valida a categoria
    $category = (string) (
        $_POST['category'] ?? ($w['category'] ?? '')
    );

    $validCategories = [];

    foreach (($d['categories'] ?? []) as $cat) {
        if (isset($cat['slug'])) {
            $validCategories[] = (string) $cat['slug'];
        }
    }

    if (!in_array($category, $validCategories, true)) {
        $errors[] = 'Selecione uma categoria válida.';
    }

    // Valida a situação
    $status = (string) ($_POST['status'] ?? '');

    $validStatuses = [
        'Em andamento',
        'Concluída',
        'Cancelada'
    ];

    if (!in_array($status, $validStatuses, true)) {
        $errors[] = 'Selecione uma situação válida.';
    }

    // Salva somente se não houver erros
    if (empty($errors)) {

        foreach ($fields as $field) {
            $w[$field] = $updated[$field];
        }

        $w['category'] = $category;
        $w['status'] = $status;

        $d['works'][$idx] = $w;

        data_write($d);

        audit_event(
            'obra_editada',
            $w['title']
        );

        header(
            'Location: ' . url('people/admin/index.php'),
            true,
            303
        );

        exit;
    }

    // Mantém os valores preenchidos caso haja erros
    foreach ($fields as $field) {
        $w[$field] = $updated[$field];
    }

    $w['category'] = $category;
    $w['status'] = $status;
}

// O cabeçalho só é incluído depois do possível redirecionamento
require __DIR__ . '/../../includes/header.php';

?>

<div class="formbox formbox-wide">

    <h1>Editar obra</h1>

    <?php if (!empty($errors)): ?>
        <div class="error-message" role="alert">
            <strong>Corrija os seguintes problemas:</strong>

            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">

        <input
            type="hidden"
            name="csrf"
            value="<?= e(csrf()) ?>"
        >

        <?php
        $textFields = [
            'title'         => 'Título',
            'original_title'=> 'Título original',
            'creator'       => 'Criador',
            'creator_url'   => 'Link do canal',
            'year'          => 'Ano',
            'rating'        => 'Classificação indicativa',
            'image'         => 'Caminho da capa'
        ];
        ?>

        <?php foreach ($textFields as $field => $label): ?>

            <label for="<?= e($field) ?>">
                <?= e($label) ?>
            </label>

            <input
                class="input"
                id="<?= e($field) ?>"
                name="<?= e($field) ?>"
                value="<?= e($w[$field] ?? '') ?>"
                <?php if ($field === 'creator_url'): ?>
                    type="url"
                    placeholder="https://..."
                <?php elseif ($field === 'year'): ?>
                    type="number"
                    min="1880"
                    max="<?= (int) date('Y') + 5 ?>"
                <?php else: ?>
                    type="text"
                <?php endif; ?>
                <?php if ($field === 'title'): ?>
                    required
                <?php endif; ?>
            >

        <?php endforeach; ?>

        <label for="category">Categoria</label>

        <select
            class="select"
            id="category"
            name="category"
            required
        >
            <?php foreach (($d['categories'] ?? []) as $cat): ?>

                <?php
                $slug = (string) ($cat['slug'] ?? '');
                $name = (string) ($cat['name'] ?? '');
                ?>

                <option
                    value="<?= e($slug) ?>"
                    <?= ($w['category'] ?? '') === $slug
                        ? 'selected'
                        : '' ?>
                >
                    <?= e($name) ?>
                </option>

            <?php endforeach; ?>
        </select>

        <label for="status">Situação</label>

        <select
            class="select"
            id="status"
            name="status"
            required
        >
            <?php
            $validStatuses = [
                'Em andamento',
                'Concluída',
                'Cancelada'
            ];
            ?>

            <?php foreach ($validStatuses as $st): ?>

                <option
                    value="<?= e($st) ?>"
                    <?= ($w['status'] ?? 'Em andamento') === $st
                        ? 'selected'
                        : '' ?>
                >
                    <?= e($st) ?>
                </option>

            <?php endforeach; ?>
        </select>

        <?php
        $textAreas = [
            'description' => 'Sinopse curta',
            'synopsis'    => 'Sinopse completa',
            'story'       => 'História da obra',
            'warning'     => 'Avisos de conteúdo sensível'
        ];
        ?>

        <?php foreach ($textAreas as $field => $label): ?>

            <label for="<?= e($field) ?>">
                <?= e($label) ?>
            </label>

            <textarea
                class="textarea"
                id="<?= e($field) ?>"
                name="<?= e($field) ?>"
                rows="4"
            ><?= e($w[$field] ?? '') ?></textarea>

        <?php endforeach; ?>

        <button type="submit" class="btn">
            Salvar alterações
        </button>

        <a
            class="btn ghost"
            href="<?= e(url('people/admin/index.php')) ?>"
        >
            Cancelar
        </a>

    </form>

</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

