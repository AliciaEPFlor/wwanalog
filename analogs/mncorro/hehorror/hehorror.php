<?php
/* WWAnalog — Página informativa da obra analógica hehorror; apresenta sua descrição, dados registrados e episódios relacionados. */
$pageTitle = 'HeHorror';
require_once __DIR__ . '/../../../includes/header.php';
?>
<main class="container">
  <section class="hero">
    <div class="meta"><span class="tag">Analog Horror / gravação</span><span class="tag">Arquivo WWAnalog</span></div>
    <h1>HeHorror</h1>
    <p>Uma produção apresentada como gravação antiga, com ruído visual e atmosfera de horror.</p>
  </section>
  <section class="section">
    <div class="card">
      <img class="analog-image" src="<?=e(url('assets/heh.png'))?>" alt="HeHorror">
      <div class="card-body">
        <h2>Sobre este Analog</h2>
        <p>Uma produção apresentada como gravação antiga, com ruído visual e atmosfera de horror.</p>
        <p><strong>Categoria:</strong> Analog Horror / gravação</p>
      </div>
    </div>
  </section>
  <section class="section grid">
    <div class="card"><div class="card-body"><h2>Arquivo</h2><p>Esta página reúne o material visual enviado para o arquivo WWAnalog.</p></div></div>
    <div class="card"><div class="card-body"><h2>Episódios</h2><p>Os episódios podem ser adicionados posteriormente dentro da pasta <strong>ep</strong> deste projeto.</p></div></div>
  </section>
</main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
