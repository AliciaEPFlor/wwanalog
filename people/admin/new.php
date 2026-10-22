<?php
/* WWAnalog — Formulário administrativo legado para cadastrar obras no armazenamento local. */

// Carrega as funções auxiliares do sistema.
// O caminho tem "../../" porque este arquivo está dentro da subpasta people/admin/.
require_once __DIR__ . '/../../includes/functions.php';

// Exige que o usuário logado seja ADMINISTRADOR. Se não for, bloqueia o acesso.
require_admin();

// Define o título da página e marca "admin" como aba ativa do menu.
$page_title = 'Registrar obra';
$active = 'admin';

// Carrega o topo do site (logo, menu, etc.).
require __DIR__ . '/../../includes/header.php';

// Verifica o token de segurança (CSRF) para evitar envios falsos de formulário.
check_csrf();

// Vai no "estoque" (arquivo de dados) e traz todas as obras e categorias.
$d = data_read();

// Prepara uma variável para guardar mensagens de erro.
$err = '';

// ===== PROCESSAMENTO DO FORMULÁRIO (SÓ SE ENVIOU ALGO) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Pega o título e o slug (identificador) enviados, tirando espaços extras.
    $title = trim($_POST['title'] ?? '');
    $slug  = trim($_POST['slug'] ?? '');

    // ===== VALIDAÇÕES =====

    // Validação 1: título obrigatório e slug no formato correto
    // (só letras minúsculas, números e hífen).
    if ($title === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
        $err = 'Informe um título e um identificador simples (letras minúsculas, números e hífen).';

    // Validação 2: verifica se já existe uma obra com esse slug.
    } elseif (find_work($slug)) {
        $err = 'Já existe uma obra com esse identificador.';

    // Se passou em todas as validações, cria a nova obra.
    } else {

        // Gera um novo ID: pega o maior ID existente e soma 1.
        // Se não houver nenhuma obra, começa do 0 e soma 1 (= 1).
        $id = max(array_column($d['works'], 'id') ?: [0]) + 1;

        // Adiciona a nova obra na lista de obras do "estoque".
        // Cada campo é preenchido com o valor do formulário, ou com um padrão.
        $d['works'][] = [
            'id'             => $id,
            'slug'           => $slug,
            'title'          => $title,

            // Título original: se não vier, usa o próprio título.
            'original_title' => trim($_POST['original_title'] ?? $title),

            // Categoria: se não vier, usa 'foundfootage'.
            'category'       => $_POST['category'] ?? 'foundfootage',

            // Tag gerada automaticamente a partir do slug:
            // - troca hífens por espaços
            // - deixa a primeira letra de cada palavra em maiúscula
            // - remove qualquer caractere que não seja letra/número
            // - adiciona "#" na frente
            'tag'            => '#' . preg_replace('/[^A-Za-z0-9]/', '', ucwords(str_replace('-', ' ', $slug))),

            'description'    => trim($_POST['description'] ?? ''),
            'synopsis'       => trim($_POST['synopsis'] ?? ''),
            'story'          => trim($_POST['story'] ?? ''),
            'warning'        => trim($_POST['warning'] ?? ''),
            'rating'         => trim($_POST['rating'] ?? ''),
            'year'           => trim($_POST['year'] ?? ''),

            // Situação: se não vier, usa 'Em andamento'.
            'status'         => $_POST['status'] ?? 'Em andamento',

            'creator'        => trim($_POST['creator'] ?? ''),
            'creator_url'    => trim($_POST['creator_url'] ?? ''),
            'image'          => trim($_POST['image'] ?? ''),

            // Lista de episódios começa vazia.
            'episodes'       => []
        ];

        // Salva no "estoque".
        data_write($d);

        // Registra no histórico de auditoria.
        audit_event('obra_criada', $title);

        // Redireciona o admin para a página de edição da obra recém-criada
        // (para ele já poder completar os dados ou adicionar episódios).
        header('Location: ' . url('people/admin/edit.php?id=' . $id));
        exit;
    }
}
?>

<!-- ===== FORMULÁRIO DE CADASTRO ===== -->
<div class="formbox formbox-wide">

    <h1>Registrar obra</h1>

    <!-- Se houve algum erro de validação, mostra a mensagem -->
    <?php if ($err): ?>
        <div class="notice"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="post">

        <!-- Token de segurança (CSRF) -->
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <!-- Campo: título exibido (obrigatório) -->
        <label>Título exibido</label>
        <input class="input" name="title" required>

        <!-- Campo: identificador/slug (obrigatório).
             Placeholder mostra o formato esperado ("nome-da-obra"). -->
        <label>Identificador (slug)</label>
        <input class="input" name="slug" placeholder="nome-da-obra" required>

        <!-- Campo: título original (opcional) -->
        <label>Título original</label>
        <input class="input" name="original_title">

        <!-- Campo: categoria (menu suspenso).
             As opções vêm das categorias cadastradas no "estoque". -->
        <label>Categoria</label>
        <select class="select" name="category">
            <?php foreach ($d['categories'] as $c): ?>
                <option value="<?= e($c['slug']) ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Campo: nome do criador -->
        <label>Criador</label>
        <input class="input" name="creator">

        <!-- Campo: link do canal do criador (tipo URL) -->
        <label>Link do canal</label>
        <input class="input" name="creator_url" type="url">

        <!-- Campo: ano -->
        <label>Ano</label>
        <input class="input" name="year">

        <!-- Campo: situação (menu suspenso com 3 opções fixas) -->
        <label>Situação</label>
        <select class="select" name="status">
            <option>Em andamento</option>
            <option>Concluída</option>
            <option>Cancelada</option>
        </select>

        <!-- Campo: classificação indicativa (texto livre).
             Placeholder avisa que é uma classificação NÃO OFICIAL. -->
        <label>Classificação indicativa</label>
        <input class="input" name="rating" placeholder="Ex.: 14+ (não oficial) ">

        <!-- Campo: caminho da imagem de capa -->
        <label>Caminho da capa (ex.: assets/capa.jpg)</label>
        <input class="input" name="image">

        <!-- Campo: sinopse curta (área de texto com 3 linhas) -->
        <label>Sinopse curta</label>
        <textarea class="textarea" name="description" rows="3"></textarea>

        <!-- Campo: sinopse completa (área de texto com 4 linhas) -->
        <label>Sinopse completa</label>
        <textarea class="textarea" name="synopsis" rows="4"></textarea>

        <!-- Campo: história da obra (área de texto com 4 linhas) -->
        <label>História da obra</label>
        <textarea class="textarea" name="story" rows="4"></textarea>

        <!-- Campo: avisos de conteúdo sensível (área de texto com 3 linhas) -->
        <label>Avisos de conteúdo sensível</label>
        <textarea class="textarea" name="warning" rows="3"></textarea>

        <!-- Botão para enviar o formulário e criar a obra -->
        <button class="btn">Registrar obra</button>
    </form>
</div>

<?php
// Carrega o rodapé do site (scripts, fechamento do HTML).
require __DIR__ . '/../../includes/footer.php';
?>