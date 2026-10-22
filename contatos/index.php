<?php
require_once __DIR__.'/../includes/functions.php';
$page_title='Contatos';$active='contact';require __DIR__.'/../includes/header.php';
ob_start(); ?>
<div class="breadcrumbs">WWAnalog / Contatos</div>
<div class="section-head"><div><h1>Contatos</h1><small>Canais oficiais e contato da equipe</small></div></div>
<div class="contact-grid">
 <section class="panel contact-box"><div class="eyebrow">CANAIS DA COMUNIDADE</div><h2>Acesse nossos canais</h2>
  <div class="contact-list">
   <div><strong>Acesse nosso fórum no Discord:</strong><span class="pending-link">link do fórum</span></div>
   <div><strong>Acesse nosso grupo no Telegram:</strong><span class="pending-link">link do grupo</span></div>
   <div><strong>Acesse nosso grupo no WhatsApp:</strong><span class="pending-link">link do grupo</span></div>
   <div><strong>Acessar o canal do dev:</strong><span class="pending-link">link do canal do dev</span></div>
  </div>
 </section>
 <section class="panel contact-box"><div class="eyebrow">EQUIPE DE DESENVOLVIMENTO</div><h2>Contatos de dev</h2>
  <div class="contact-list">
   <div><strong>Email</strong><a href="mailto:aliciaelizabethpilarflor@gmail.com">aliciaelizabethpilarflor@gmail.com</a></div>
   <div><strong>Discord</strong><span>_*oleandro.*_verde_</span></div>
   <div><strong>Instagram Oficial</strong><span>_oleandro._verde_</span></div>
   <div><strong>LinkedIn</strong><a href="https://www.linkedin.com/in/alicia-elizabeth-pilar-flor/" target="_blank" rel="noopener">alicia-elizabeth-pilar-flor</a></div>
   <div><strong>GitHub</strong><a href="https://github.com/AliciaEPFlor" target="_blank" rel="noopener">AliciaEPFlor</a></div>
  </div>
 </section>
</div>
<?php $content=ob_get_clean();shell($content,$active);require __DIR__.'/../includes/footer.php'; ?>
