<?php
/* WWAnalog — Página de episódios/links relacionados à obra mfpnpark; apresenta conteúdo da obra e seus episódios. */
require_once __DIR__ . '/../../../../includes/functions.php';
$w = find_work('mfpnpark');
if (!$w) { http_response_code(404); exit('Obra não encontrada.'); }
header('Location: '.url('episodios.php?obra=' . (int)$w['id']));
exit;
