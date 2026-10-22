<!-- WWAnalog: Formulário administrativo legado para editar obras no armazenamento local. -->

<?php
// Carrega as funções auxiliares do sistema.
// O caminho tem "../../" porque este arquivo está dentro da subpasta people/admin/.
require_once __DIR__ . '/../../includes/functions.php';

// Exige que o usuário logado seja ADMINISTRADOR. Se não for, bloqueia o acesso.
require_admin();

// Define o título da página e marca "admin" como aba ativa do menu.
$page_title = 'Editar obra';
$active = 'admin';

// ===== CARREGA OS DADOS =====
// Vai no "estoque" (arquivo de dados) e traz todas as obras cadastradas.
$d = data_read();

// Pega o ID da obra que se quer editar (vem pela URL: ?id=123).
// Se não vier nada, fica vazio.
$id = (string) ($_GET['id'] ?? '');
$idx = null;

// ===== PROCURA A OBRA NA LISTA =====
// Percorre todas as obras do "estoque" e guarda a posição (índice) da que tem o ID procurado.
foreach (($d['works'] ?? []) as $i => $work) {
    if ((string) ($work['id'] ?? '') === $id && $id !== '') {
        $idx = $i;   // Achou! Guarda a posição dela na lista.
        break;       // Para de procurar.
    }
}

// Se não encontrou nenhuma obra com esse ID, mostra erro 404 e para tudo.
if ($idx === null) {
    http_response_code(404);
    exit('Obra não encontrada.');
}

// Guarda os dados da obra encontrada numa variável curta.
$w = $d['works'][$idx];

// Prepara uma lista vazia para guardar mensagens de erro de validação.
$errors = [];

// ===== LISTA DE CAMPOS DE TEXTO DO FORMULÁRIO =====
// Estes são os campos que serão tratados como texto simples.
// Usamos essa lista para percorrer e atualizar todos de uma vez.
$fields = [
    'title',           // Título
    'original_title',  // Título original
    'description',     // Sinopse curta
    'synopsis',        // Sinopse completa
    'story',           // História da obra
    'creator',         // Criador
    'creator_url',     // Link do canal do criador
    'year',            // Ano
    'rating',          // Classificação indicativa
    'warning',         // Avisos de conteúdo
    'image'            // Caminho da capa
];

// ===== PROCESSAMENTO DO FORMULÁRIO (SÓ SE ENVIOU ALGO) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Valida o token de segurança (CSRF) para evitar envios falsos.
    check_csrf();

    // ===== RECOLHE OS CAMPOS ENVIADOS =====
    // Cria um array vazio para guardar os valores atualizados.
    $updated = [];

    // Percorre a lista de campos, pega o valor de cada um e tira espaços extras.
    foreach ($fields as $field) {
        $updated[$field] = trim(
            (string) ($_POST[$field] ?? '')
        );
    }

    // ===== VALIDAÇÕES =====

    // Validação 1: o título é obrigatório (não pode ficar vazio).
    if ($updated['title'] === '') {
        $errors[] = 'O título da obra é obrigatório.';
    }

    // Validação 2: o ano, se preenchido, precisa ser um número válido.
    // O campo pode ficar vazio (aí não valida).
    if ($updated['year'] !== '') {
        if (
            !ctype_digit($updated['year']) ||                 // Tem que ser só dígitos
            (int) $updated['year'] < 1880 ||                  // Não pode ser antes de 1880
            (int) $updated['year'] > (int) date('Y') + 5      // Não pode ser mais de 5 anos no futuro
        ) {
            $errors[] = 'Informe um ano válido.';
        }
    }

    // Validação 3: o link do criador, se preenchido, precisa ser uma URL válida
    // e começar com http ou https.
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

    // ===== VALIDA A CATEGORIA =====
    // Pega a categoria enviada. Se não vier nada, mantém a que já estava.
    $category = (string) (
        $_POST['category'] ?? ($w['category'] ?? '')
    );

    // Monta a lista de categorias válidas (os "slugs" cadastrados).
    $validCategories = [];
    foreach (($d['categories'] ?? []) as $cat) {
        if (isset($cat['slug'])) {
            $validCategories[] = (string) $cat['slug'];
        }
    }

    // Se a categoria escolhida NÃO estiver na lista de válidas, dá erro.
    if (!in_array($category, $validCategories, true)) {
        $errors[] = 'Selecione uma categoria válida.';
    }

    // ===== VALIDA A SITUAÇÃO =====
    // Pega a situação enviada.
    $status = (string) ($_POST['status'] ?? '');

    // Lista de situações permitidas.
    $validStatuses = [
        'Em andamento',
        'Concluída',
        'Cancelada'
    ];

    // Se a situação escolhida NÃO estiver na lista, dá erro.
    if (!in_array($status, $validStatuses, true)) {
        $errors[] = 'Selecione uma situação válida.';
    }

    // ===== SALVA SE NÃO HOUVER ERROS =====
    if (empty($errors)) {

        // Atualiza cada campo de texto da obra com o valor enviado.
        foreach ($fields as $field) {
            $w[$field] = $updated[$field];
        }

        // Atualiza categoria e situação.
        $w['category'] = $category;
        $w['status'] = $status;

        // Coloca a obra atualizada de volta na lista, na mesma posição.
        $d['works'][$idx] = $w;

        // Salva no "estoque".
        data_write($d);

        // Registra no histórico de auditoria.
        audit_event(
            'obra_editada',
            $w['title']
        );

        // Redireciona o admin de volta para o painel (status 303 = "veja outra página").
        header(
            'Location: ' . url('people/admin/index.php'),
            true,
            303
        );

        exit;  // Para tudo (o redirecionamento já foi enviado).
    }

    // ===== SE HOUVE ERROS =====
    // Mantém os valores preenchidos no formulário (para o admin não perder o que digitou).
    foreach ($fields as $field) {
        $w[$field] = $updated[$field];
    }

    $w['category'] = $category;
    $w['status'] = $status;
}

// ===== CARREGA O CABEÇALHO DO SITE =====
// Só é incluído DEPOIS do possível redirecionamento, para não dar erro de "headers já enviados".
require __DIR__ . '/../../includes/header.php';

?>

<!-- ===== FORMULÁRIO DE EDIÇÃO ===== -->
<div class="formbox formbox-wide">

    <h1>Editar obra</h1>

    <!-- Se houver erros de validação, mostra uma caixa com a lista deles -->
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

        <!-- Token de segurança (CSRF) -->
        <input
            type="hidden"
            name="csrf"
            value="<?= e(csrf()) ?>"
        >

        <?php
        // ===== CAMPOS DE TEXTO =====
        // Mapeia cada campo a um rótulo amigável para exibir no formulário.
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

        <!-- Gera automaticamente um campo para cada item da lista acima -->
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

        <!-- ===== CAMPO DE CATEGORIA (menu suspenso) ===== -->
        <label for="category">Categoria</label>

        <select
            class="select"
            id="category"
            name="category"
            required
        >
            <?php foreach (($d['categories'] ?? []) as $cat): ?>

                <?php
                // Pega o slug e o nome da categoria.
                $slug = (string) ($cat['slug'] ?? '');
                $name = (string) ($cat['name'] ?? '');
                ?>

                <!-- Cada opção mostra o nome e envia o slug.
                     Se for a categoria atual da obra, marca como "selected". -->
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

        <!-- ===== CAMPO DE SITUAÇÃO (menu suspenso) ===== -->
        <label for="status">Situação</label>

        <select
            class="select"
            id="status"
            name="status"
            required
        >
            <?php
            // Lista das situações permitidas.
            $validStatuses = [
                'Em andamento',
                'Concluída',
                'Cancelada'
            ];
            ?>

            <?php foreach ($validStatuses as $st): ?>

                <!-- Marca como "selected" a situação atual da obra. -->
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
        // ===== CAMPOS DE TEXTO LONGO (áreas de texto) =====
        // Mapeia cada campo a um rótulo amigável.
        $textAreas = [
            'description' => 'Sinopse curta',
            'synopsis'    => 'Sinopse completa',
            'story'       => 'História da obra',
            'warning'     => 'Avisos de conteúdo sensível'
        ];
        ?>

        <!-- Gera automaticamente uma área de texto para cada item acima -->
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

        <!-- Botão para salvar as alterações -->
        <button type="submit" class="btn">
            Salvar alterações
        </button>

        <!-- Botão para cancelar (volta ao painel sem salvar) -->
        <a
            class="btn ghost"
            href="<?= e(url('people/admin/index.php')) ?>"
        >
            Cancelar
        </a>

    </form>

</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

