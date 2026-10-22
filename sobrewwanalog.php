<?php
/* WWAnalog — Página institucional que explica o propósito do WWAnalog. */ $page_title = 'Sobre a WWAnalog';
$active = 'about';
require __DIR__ . '/includes/header.php';
ob_start(); ?>
<div class="hero">
    <div class="eyebrow">WWANALOG // IDENTIDADE</div>
    <h1>A empresa, o site</h1>
    <p>A WWAnalog é uma proposta de arquivo independente dedicada à organização, consulta e descoberta de obras de Analog Horror. O site procura reunir títulos, imagens, categorias, informações e caminhos para os conteúdos originais em uma interface única, com uma identidade visual inspirada em arquivos, transmissões e mídias analógicas.</p>
</div>
<div class="about-grid">
    <article class="card">
        <div class="category-number">01 // HISTÓRIA DO NEGÓCIO</div>
        <h2>História</h2>
        <p class="desc">A WWAnalog nasce da necessidade de organizar informações que normalmente ficam espalhadas entre vídeos, canais, wikis e comunidades. A proposta é transformar essa pesquisa fragmentada em um catálogo navegável, no qual cada obra tenha uma entrada própria e possa ser consultada por categoria.</p>
        <p class="desc">O projeto também funciona como um exercício de construção de produto digital: identidade visual, navegação, catálogo, páginas individuais, contas de usuário e recursos de comunidade são pensados como partes de uma mesma experiência.</p>
    </article>
    <article class="card">
        <div class="category-number">02 // MISSÃO</div>
        <h2>Missão</h2>
        <p class="desc">A missão é facilitar a descoberta e a organização de obras de Analog Horror, oferecendo contexto suficiente para que o visitante entenda o que está acessando e encontre rapidamente a página, episódio ou fonte que procura.</p>
        <p class="desc">A plataforma também busca respeitar os criadores: o catálogo funciona como referência e organização, não como substituição dos canais oficiais onde as obras são publicadas.</p>
    </article>
    <article class="card">
        <div class="category-number">03 // VISÃO</div>
        <h2>Visão</h2>
        <p class="desc">A visão da WWAnalog é tornar-se um ponto de referência independente para catalogação e descoberta de Analog Horror, reunindo obras conhecidas e registros menores sem perder a organização necessária para que o catálogo continue fácil de consultar.</p>
        <p class="desc">No longo prazo, o arquivo pode crescer com novas categorias, episódios, informações de criadores, filtros e contribuições da comunidade, sempre mantendo uma estrutura clara.</p>
    </article>
    <article class="card">
        <div class="category-number">04 // VALORES</div>
        <h2>Valores</h2>
        <p class="desc"><strong>Organização:</strong> informações devem ser fáceis de encontrar e comparar. <strong>Transparência:</strong> o site deve diferenciar dados cadastrados de informações que ainda precisam de confirmação.</p>
        <p class="desc"><strong>Respeito aos criadores:</strong> links e referências devem apontar para as fontes originais quando disponíveis. <strong>Acessibilidade:</strong> a navegação deve ser compreensível e funcional. <strong>Comunidade:</strong> sugestões e correções podem ajudar o arquivo a melhorar.</p>
    </article>
    <article class="card">
        <div class="category-number">05 // O SITE</div>
        <h2>Como a plataforma funciona</h2>
        <p class="desc">O catálogo organiza as obras em cards com capa, título, categoria, descrição, classificação indicativa e avisos de conteúdo sensível. Cada obra pode possuir uma página própria, com informações adicionais e acesso aos episódios ou fontes externas.</p>
        <p class="desc">As páginas institucionais explicam o gênero, apresentam a proposta do projeto e reúnem os canais oficiais de contato. O acesso é pensado para que o visitante consiga sair da página inicial e chegar ao conteúdo desejado sem depender de uma navegação complicada.</p>
    </article>
    <article class="card">
        <div class="category-number">06 // RESPONSABILIDADE</div>
        <h2>Compromisso com o arquivo</h2>
        <p class="desc">A WWAnalog é um catálogo independente. A classificação indicativa exibida nas fichas é uma orientação editorial do próprio arquivo e não deve ser confundida com uma classificação oficial de órgão regulador.</p>
        <p class="desc">Quando uma informação estiver incompleta, a plataforma deve sinalizar essa condição em vez de apresentar uma hipótese como fato. Para correções, atualizações ou pedidos relacionados ao conteúdo, a equipe mantém canais próprios de contato.</p>
    </article>
</div>
<section class="institutional-image">
    <div class="institutional-placeholder"><span>WWANALOG</span><small>ARQUIVO INDEPENDENTE // IDENTIDADE INSTITUCIONAL</small></div>
</section>
<?php $content = ob_get_clean();
shell($content, $active);
require __DIR__ . '/includes/footer.php'; ?>