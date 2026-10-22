<?php
/* WWAnalog — Página explicativa sobre o gênero Analog Horror e a organização do catálogo. */

// Define o título da página (aparece na aba do navegador)
$page_title = 'O que é';

// Diz ao menu qual aba deve ficar destacada (a aba "Info")
$active = 'info';

// Carrega o topo do site (logo, menu, etc.)
require __DIR__ . '/includes/header.php';

// Vai no "estoque" e traz os dados do catálogo (obras e categorias)
$d = data_read();

// ===== TEXTOS EXPLICATIVOS DE CADA CATEGORIA =====
// Aqui criamos um "dicionário" onde cada chave (slug da categoria) tem
// um texto explicativo que será mostrado no cartão correspondente.
// É como um glossário: para cada tipo de obra, uma descrição pronta.
$descs = [
    'foundfootage' => 'A narrativa se apresenta como material encontrado: fitas, gravações de câmera, arquivos recuperados ou vídeos publicados depois de algum acontecimento. O horror nasce da sensação de que o espectador está vendo uma evidência que não deveria existir.',

    'bcasthijacks' => 'A história é contada por meio de televisão, rádio, canais locais, alertas e transmissões interrompidas ou alteradas. A quebra da programação normal transforma uma mídia familiar em um sinal de ameaça.',

    'cttpsas' => 'Utiliza vídeos de treinamento, fitas educativas, anúncios públicos, instruções de segurança e comunicações institucionais. A linguagem objetiva desses materiais contrasta com informações anômalas, perigosas ou perturbadoras.',

    'lostmedia' => 'Explora a ideia de uma obra, gravação, jogo ou programa que desapareceu, foi apagado, ficou incompleto ou voltou a ser encontrado. O próprio processo de procurar a mídia passa a fazer parte do mistério.',

    'args' => 'ARGs distribuem pistas e acontecimentos por diferentes mídias, páginas, vídeos, documentos ou mensagens. O público participa ativamente da investigação, conectando fragmentos para reconstruir a narrativa ficcional.',

    'liminalsp' => 'Usa espaços vazios, repetitivos, enormes ou deslocados de sua função cotidiana para produzir estranheza. Corredores, estacionamentos, shoppings, prédios e ambientes aparentemente comuns podem se tornar ameaçadores quando não há pessoas, saída ou lógica aparente.',

    'mncorro' => 'Trabalha com lembranças, programas infantis, comerciais, músicas e referências nostálgicas que são gradualmente deformadas. O familiar continua reconhecível, mas passa a carregar sinais de perigo, perda ou algo que está errado.',

    'animais' => 'Coloca animais, plantas, florestas, parques ou fenômenos naturais no centro do horror. A ameaça pode surgir de alterações biológicas, criaturas desconhecidas, comportamentos impossíveis ou ambientes naturais que escondem algo.',

    'jogos' => 'Utiliza jogos, consoles, computadores, softwares, arquivos e mídias interativas como parte da narrativa. Erros, arquivos ocultos, personagens corrompidos e comportamentos impossíveis fazem o próprio sistema parecer estar participando do horror.',

    'substituicao' => 'Explora cópias, sósias, duplicatas ou entidades que ocupam o lugar de pessoas. A tensão costuma vir da dúvida sobre identidade, memória e confiança: quem está diante do personagem pode não ser quem parece.'
];

// Começa a "gravar" tudo o que for desenhado abaixo numa folha de rascunho
ob_start();
?>

<!-- ===== BANNER PRINCIPAL (HERO) ===== -->
<div class="hero">
    <!-- Linha pequena acima do título -->
    <div class="eyebrow">DOCUMENTAÇÃO // GÊNERO</div>

    <!-- Título grande da página -->
    <h1>Analong Horror</h1>

    <!-- Parágrafo explicando o gênero de forma resumida -->
    <p>Analog Horror é um subgênero de ficção de terror que imita a linguagem de mídias antigas — como televisão, VHS, fitas educativas, gravações domésticas, rádio e sistemas de emergência — para contar histórias de mistério, ameaça e estranheza. Em vez de depender apenas de sustos, muitas obras usam ruído, baixa fidelidade, mensagens incompletas, silêncios, imagens estranhas e informações fragmentadas para fazer o público investigar o que está acontecendo.</p>
</div>

<!-- ===== DUAS COLUNAS COM EXPLICAÇÕES ===== -->
<div class="two-col info-explainer">

    <!-- Coluna 1: como o gênero funciona -->
    <section class="panel">
        <div class="eyebrow">01 // COMO FUNCIONA</div>
        <h2>O que torna uma obra Analog Horror?</h2>

        <!-- Parágrafo 1: sobre a linguagem da mídia -->
        <p>O elemento central é a <strong>linguagem da mídia</strong>. A obra pode parecer um telejornal antigo, uma fita institucional, uma gravação encontrada ou um arquivo digital recuperado. A estética ajuda a construir a sensação de autenticidade, como se aquele material fosse um documento dentro do universo da história.</p>

        <!-- Parágrafo 2: sobre a informação incompleta -->
        <p>Outro recurso frequente é a <strong>informação incompleta</strong>. Em vez de explicar tudo, a narrativa deixa pistas, contradições e acontecimentos fora de quadro. O espectador precisa montar hipóteses, comparar vídeos e interpretar detalhes.</p>

        <!-- Parágrafo 3: sobre a estranheza progressiva -->
        <p>O gênero também costuma trabalhar com <strong>estranheza progressiva</strong>: algo começa normal e pequenas alterações fazem o material parecer cada vez mais errado. A familiaridade da televisão, da publicidade ou de um jogo é usada justamente para tornar a quebra dessa normalidade mais desconfortável.</p>
    </section>

    <!-- Coluna 2: elementos comuns do gênero -->
    <section class="panel">
        <div class="eyebrow">02 // RECURSOS</div>
        <h2>Elementos comuns</h2>

        <!-- Lista com os recursos mais usados nesse tipo de obra -->
        <ul class="info-list">
            <li><strong>Estética analógica:</strong> ruído, VHS, interferência, scanlines e baixa resolução.</li>
            <li><strong>Transmissões:</strong> canais, alertas, programas e mensagens que são interrompidos ou corrompidos.</li>
            <li><strong>Fitas e arquivos:</strong> registros recuperados, documentos e gravações que funcionam como evidências.</li>
            <li><strong>Mensagens enigmáticas:</strong> textos, símbolos, instruções e avisos que escondem informações.</li>
            <li><strong>Horror psicológico:</strong> medo, paranoia, isolamento e dúvida sobre o que é real.</li>
            <li><strong>Horror corporal ou cósmico:</strong> quando a ameaça envolve transformação, criaturas ou fenômenos maiores que os personagens.</li>
        </ul>
    </section>
</div>

<!-- ===== CABEÇALHO DA SEÇÃO DE CATEGORIAS ===== -->
<div class="section-head">
    <div>
        <h2>Categorias</h2>
        <small>As categorias são uma forma de organizar diferentes linguagens e temas usados pelo arquivo.</small>
    </div>
</div>

<!-- ===== GRADE COM OS CARTÕES DE CADA CATEGORIA ===== -->
<div class="category-info-grid">

    <!-- Para cada categoria do catálogo, cria um cartão explicativo -->
    <?php foreach ($d['categories'] as $i => $c): ?>

        <article class="card category-info">

            <!-- Número da categoria formatado com 2 dígitos (CAT. 01, CAT. 02, ...) -->
            <div class="category-number">CAT. <?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></div>

            <!-- Nome da categoria -->
            <h2><?= e($c['name']) ?></h2>

            <!-- Texto explicativo:
                 - Se existir no dicionário $descs, usa o texto específico da categoria
                 - Se NÃO existir, usa um texto genérico padrão -->
            <p class="desc"><?= e($descs[$c['slug']] ?? 'Categoria utilizada para organizar obras com características relacionadas a este tipo de narrativa.') ?></p>
        </article>
    <?php endforeach; ?>
</div>

<?php
// Termina a "gravação" do rascunho e guarda tudo na variável $content
$content = ob_get_clean();

// Encaixa o rascunho dentro do layout do site
shell($content, $active);

// Carrega o rodapé do site (scripts, fechamento do HTML)
require __DIR__ . '/includes/footer.php';
?>