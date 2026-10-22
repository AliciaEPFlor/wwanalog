<?php
/* WWAnalog — Página informativa da obra analógica chimpy; apresenta sua descrição, dados registrados e episódios relacionados. */
$pageTitle = "C. Chippa's Commercial";
require_once __DIR__ . '/../../../includes/header.php';
?>
<main class="container">
  <section class="hero">
    <div class="meta"><span class="tag">Lost Media / comercial</span><span class="tag">Arquivo WWAnalog</span></div>
    <h1>C. Chippa's Commercial</h1>
    <p>Uma peça publicitária antiga e inquietante, construída com estética infantil e aparência de fita VHS.</p>
  </section>
  <section class="section">
    <div class="card">
      <img class="analog-image" src="<?=e(url('assets/chimpy.png'))?>" alt="C. Chippa's Commercial">
      <div class="card-body">
        <h2>Sobre este Analog</h2>
        <p>Uma peça publicitária antiga e inquietante, construída com estética infantil e aparência de fita VHS.</p>
        <p><strong>Categoria:</strong> Lost Media / comercial</p>
      </div>
    </div>
  </section>
  <section class="section grid">
    <div class="card"><div class="card-body"><h2>Arquivo</h2><p>Esta página reúne o material visual enviado para o arquivo WWAnalog.</p></div></div>
    <div class="card"><div class="card-body"><h2>Episódios</h2><p>Os episódios podem ser adicionados posteriormente dentro da pasta <strong>ep</strong> deste projeto.</p></div></div>
  </section>
</main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
