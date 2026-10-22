<?php
/* WWAnalog — Página informativa da obra analógica chss; apresenta sua descrição, dados registrados e episódios relacionados. */
$pageTitle = 'CH/SS';
require_once __DIR__ . '/../../../includes/header.php';
?>
<main class="container">
  <section class="hero">
    <div class="meta"><span class="tag">Broadcast / transmissão</span><span class="tag">Arquivo WWAnalog</span></div>
    <h1>CH/SS</h1>
    <p>Uma transmissão de aparência documental que utiliza imagens urbanas e uma atmosfera de estranhamento.</p>
  </section>
  <section class="section">
    <div class="card">
      <img class="analog-image" src="<?=e(url('assets/chss.jpg'))?>" alt="CH/SS">
      <div class="card-body">
        <h2>Sobre este Analog</h2>
        <p>Uma transmissão de aparência documental que utiliza imagens urbanas e uma atmosfera de estranhamento.</p>
        <p><strong>Categoria:</strong> Broadcast / transmissão</p>
      </div>
    </div>
  </section>
  <section class="section grid">
    <div class="card"><div class="card-body"><h2>Arquivo</h2><p>Esta página reúne o material visual enviado para o arquivo WWAnalog.</p></div></div>
    <div class="card"><div class="card-body"><h2>Episódios</h2><p>Os episódios podem ser adicionados posteriormente dentro da pasta <strong>ep</strong> deste projeto.</p></div></div>
  </section>
</main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
