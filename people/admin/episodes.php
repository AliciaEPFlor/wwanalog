<?php
/* WWAnalog — Gerenciamento legado de episódios, usando o armazenamento local. */

// Carrega as funções auxiliares do sistema.
// O caminho tem "../../" porque este arquivo está dentro da subpasta people/admin/.
require_once __DIR__ . '/../../includes/functions.php';

// Exige que o usuário logado seja ADMINISTRADOR. Se não for, bloqueia o acesso.
require_admin();

// Define o título da página e marca "admin" como aba ativa do menu.
$page_title = 'Gerenciar episódios';
$active = 'admin';

// Carrega o topo do site (logo, menu, etc.).
require __DIR__ . '/../../includes/header.php';

// Verifica o token de segurança (CSRF) para evitar envios falsos de formulário.
check_csrf();

// Vai no "estoque" (arquivo de dados) e traz todos os dados.
$d = data_read();

// Prepara uma variável para guardar a mensagem de sucesso/erro.
$msg = '';

// ===== PROCESSAMENTO DO FORMULÁRIO (SÓ SE ENVIOU ALGO) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Pega o ID da obra escolhida no formulário.
    $wid = (int)($_POST['work_id'] ?? 0);

    // Percorre TODAS as obras procurando a que tem esse ID.
    // O "&" antes de $w permite alterar a obra dentro do laço.
    foreach ($d['works'] as &$w) {

        // Quando encontra a obra certa...
        if ((int)$w['id'] === $wid) {

            // ===== CASO 1: O ADMIN QUER ADICIONAR UM EPISÓDIO =====
            if (isset($_POST['add_episode'])) {

                // Pega o título do episódio, tirando espaços extras.
                $title = trim($_POST['title'] ?? '');

                // Só continua se o título não estiver vazio.
                if ($title !== '') {

                    // Monta os dados básicos do novo episódio:
                    //  - número = quantidade atual + 1
                    //  - título, duração, URL externa, URL de embed, descrição
                    $ep = [
                        'number'      => count($w['episodes'] ?? []) + 1,
                        'title'       => $title,
                        'duration'    => trim($_POST['duration'] ?? ''),
                        'url'         => trim($_POST['url'] ?? ''),
                        'embed_url'   => trim($_POST['embed_url'] ?? ''),
                        'description' => trim($_POST['description'] ?? '')
                    ];

                    // Pega o arquivo de vídeo enviado (se houver).
                    $file = $_FILES['video_file'] ?? null;

                    // Se o admin enviou um arquivo de vídeo...
                    if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {

                        // Formatos permitidos e seus tipos MIME.
                        $allowed = [
                            'mp4'  => 'video/mp4',
                            'webm' => 'video/webm',
                            'ogv'  => 'video/ogg',
                            'm4v'  => 'video/mp4'
                        ];

                        // Pega a extensão do arquivo (em minúsculas).
                        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

                        // Validação 1: houve erro no envio?
                        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                            $msg = 'Falha no envio do vídeo. Verifique os limites de upload do PHP.';

                        // Validação 2: extensão permitida e arquivo realmente enviado?
                        } elseif (!isset($allowed[$ext]) || !is_uploaded_file($file['tmp_name'])) {
                            $msg = 'Formato inválido. Use MP4, WEBM, OGV ou M4V.';

                        // Validação 3: tamanho máximo de 512 MB (536870912 bytes)?
                        } elseif (($file['size'] ?? 0) > 536870912) {
                            $msg = 'O vídeo ultrapassa o limite de 512 MB.';

                        // Se passou em todas as validações, salva o arquivo.
                        } else {
                            // Cria um "slug" seguro para a pasta (só letras, números, "_" e "-").
                            $slug = preg_replace('/[^a-z0-9_-]/i', '-', (string)($w['slug'] ?? $wid));

                            // Caminho da pasta onde os vídeos ficam: media/episodes/<slug>/
                            $dir = __DIR__ . '/../../media/episodes/' . $slug;

                            // Se a pasta não existe, cria.
                            if (!is_dir($dir)) mkdir($dir, 0755, true);

                            // Gera um nome de arquivo único:
                            // "ep-AAAAMMDDHHMMSS-<8 caracteres aleatórios>.<ext>"
                            $filename = 'ep-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

                            // Move o arquivo temporário para a pasta definitiva.
                            if (move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
                                // Guarda o caminho relativo no episódio.
                                $ep['local_video'] = 'media/episodes/' . $slug . '/' . $filename;
                                $msg = 'Episódio adicionado com vídeo hospedado no WWAnalog.';
                            } else {
                                $msg = 'Não foi possível salvar o vídeo. Verifique as permissões da pasta media/episodes.';
                            }
                        }
                    }

                    // Se NÃO houve mensagem de erro (ou seja, ou não enviou arquivo,
                    // ou o arquivo foi salvo com sucesso), prepara a mensagem de sucesso.
                    if (!$msg) {
                        $msg = 'Episódio adicionado. ' . (!empty($ep['url'])
                            ? 'Vídeo externo mantido como alternativa.'
                            : 'Envie um arquivo para hospedá-lo na aplicação.');
                    }

                    // Só adiciona o episódio à obra se a mensagem NÃO for uma das mensagens de erro.
                    // (Ou seja: se o envio deu certo ou não havia arquivo.)
                    if (empty($msg)
                        || strpos($msg, 'Falha no envio') !== 0
                        && strpos($msg, 'Formato inválido') !== 0
                        && strpos($msg, 'O vídeo ultrapassa') !== 0
                        && strpos($msg, 'Não foi possível salvar') !== 0
                    ) {
                        $w['episodes'][] = $ep;
                    }
                }

            // ===== CASO 2: O ADMIN QUER REMOVER UM EPISÓDIO =====
            } elseif (isset($_POST['delete_episode'])) {

                // Pega o índice (posição) do episódio na lista.
                $idx = (int)$_POST['episode_index'];

                // Se esse episódio existe na lista, remove.
                if (isset($w['episodes'][$idx])) {
                    // array_splice remove 1 item a partir da posição $idx.
                    array_splice($w['episodes'], $idx, 1);
                    $msg = 'Episódio removido.';
                }
            }

            // Para de procurar (já achou a obra certa).
            break;
        }
    }
    unset($w); // Boa prática: desfaz a referência após o laço.

    // Se houve alguma alteração (mensagem preenchida), salva no "estoque"
    // e registra no histórico de auditoria.
    if ($msg) {
        data_write($d);
        audit_event('episodios_atualizados', $msg . ' Obra ID ' . $wid);
    }

    // Relê os dados atualizados.
    $d = data_read();
}

// ===== FILTROS DA LISTAGEM =====

// Lê o filtro por obra e o texto de busca (via URL).
$filterWork = (string)($_GET['work_id'] ?? '');
$q = trim($_GET['q'] ?? '');

// Filtra a lista de obras que serão exibidas:
//  - Se escolheu uma obra, mostra só ela.
//  - Se digitou busca, procura no título da obra OU nos títulos dos episódios.
$visibleWorks = array_values(array_filter($d['works'], function ($w) use ($filterWork, $q) {

    // Filtro 1: obra específica.
    if ($filterWork !== '' && (string)$w['id'] !== $filterWork) return false;

    // Filtro 2: busca textual no título da obra ou nos títulos dos episódios.
    if ($q !== ''
        && stripos($w['title'] ?? '', $q) === false
        && stripos(implode(' ', array_map(fn($ep) => $ep['title'] ?? '', $w['episodes'] ?? [])), $q) === false
    ) return false;

    // Se passou pelos filtros, mantém a obra.
    return true;
}));
?>

<!-- ===== CABEÇALHO DA PÁGINA ===== -->
<div class="section-head">
    <div>
        <div class="eyebrow">PAINEL DO DESENVOLVEDOR</div>
        <h1>Gerenciar episódios</h1>
    </div>
</div>

<!-- Se houve alguma mensagem (sucesso ou erro), mostra -->
<?php if ($msg): ?>
    <div class="notice success"><?= e($msg) ?></div>
<?php endif; ?>

<!-- ===== DUAS COLUNAS: ADICIONAR (esquerda) e LISTAR (direita) ===== -->
<div class="two-col">

    <!-- ===== COLUNA 1: FORMULÁRIO DE ADICIONAR EPISÓDIO ===== -->
    <section class="panel">
        <h2>Adicionar episódio</h2>

        <!-- O "enctype=multipart/form-data" é obrigatório para enviar arquivos. -->
        <form method="post" enctype="multipart/form-data">

            <!-- Token de segurança (CSRF) -->
            <input type="hidden" name="csrf" value="<?= csrf() ?>">

            <!-- Campo: escolher a obra -->
            <label>Obra</label>
            <select class="select" name="work_id">
                <?php foreach ($d['works'] as $w): ?>
                    <option value="<?= $w['id'] ?>"><?= e($w['title']) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Campo: título do episódio (obrigatório) -->
            <label>Título do episódio</label>
            <input class="input" name="title" required>

            <!-- Campo: duração (ex.: "12 min") -->
            <label>Duração</label>
            <input class="input" name="duration" placeholder="Ex.: 12 min">

            <!-- Campo: arquivo de vídeo para hospedar no próprio site -->
            <label>Arquivo de vídeo para hospedar na aplicação (MP4, WEBM, OGV ou M4V; até 512 MB)</label>
            <input class="input" name="video_file" type="file" accept="video/mp4,video/webm,video/ogg,.m4v">

            <!-- Campo: URL externa do vídeo (opcional) -->
            <label>URL original (opcional, alternativa)</label>
            <input class="input" name="url" type="url">

            <!-- Campo: URL de embed do YouTube (opcional) -->
            <label>URL de incorporação YouTube (opcional)</label>
            <input class="input" name="embed_url" type="url" placeholder="https://www.youtube.com/embed/ID">

            <!-- Campo: descrição do episódio -->
            <label>Descrição</label>
            <textarea class="textarea" name="description" rows="3"></textarea>

            <!-- Botão para adicionar (o "name=add_episode" é o que identifica a ação) -->
            <button class="btn" name="add_episode" value="1">Adicionar episódio</button>
        </form>
    </section>

    <!-- ===== COLUNA 2: LISTA DE EPISÓDIOS JÁ CADASTRADOS ===== -->
    <section class="panel">
        <h2>Episódios cadastrados</h2>

        <!-- ===== FORMULÁRIO DE FILTROS ===== -->
        <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0">

            <!-- Campo: busca por obra ou episódio -->
            <input class="input" style="flex:1;min-width:160px" name="q" value="<?= e($q) ?>" placeholder="Buscar obra ou episódio">

            <!-- Campo: filtro por obra -->
            <select class="select" name="work_id">
                <option value="">Todas as obras</option>
                <?php foreach ($d['works'] as $optionWork): ?>
                    <option value="<?= e($optionWork['id']) ?>" <?= $filterWork === (string)$optionWork['id'] ? 'selected' : '' ?>><?= e($optionWork['title']) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Botões: aplicar filtro e limpar -->
            <button class="btn">Filtrar</button>
            <a class="btn ghost" href="<?= e(url('people/admin/episodes.php')) ?>">Limpar</a>
        </form>

        <!-- ===== LISTAGEM DE OBRAS E SEUS EPISÓDIOS ===== -->
        <?php
        // Percorre cada obra que passou pelos filtros.
        foreach ($visibleWorks as $w):

            // Só mostra obras que têm pelo menos um episódio.
            if (!empty($w['episodes'])):
        ?>
                <!-- Título da obra (cabeçalho da seção) -->
                <h3><?= e($w['title']) ?></h3>

                <?php
                // Percorre cada episódio dessa obra.
                foreach ($w['episodes'] as $i => $ep):
                ?>
                    <div class="episode">
                        <div>
                            <!-- Título do episódio (ou "Episódio" se não tiver) -->
                            <strong><?= e($ep['title'] ?? 'Episódio') ?></strong>

                            <!-- Duração + indicação se o vídeo está hospedado no site -->
                            <p><?= e($ep['duration'] ?? '') ?><?= !empty($ep['local_video']) ? ' · Hospedado no WWAnalog' : '' ?></p>
                        </div>

                        <!-- Formulário de remoção deste episódio -->
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= csrf() ?>">
                            <input type="hidden" name="work_id" value="<?= $w['id'] ?>">
                            <input type="hidden" name="episode_index" value="<?= $i ?>">
                            <button class="btn danger" name="delete_episode" value="1">Remover</button>
                        </form>
                    </div>
                <?php
                // Fim do laço de episódios.
                endforeach;

            // Fim do "if" que verifica se a obra tem episódios.
            endif;

        // Fim do laço de obras.
        endforeach;
        ?>
    </section>
</div>

<?php
// Carrega o rodapé do site (scripts, fechamento do HTML).
require __DIR__ . '/../../includes/footer.php';
?>