<?php
/* WWAnalog — Encerra a sessão autenticada e redireciona o usuário. */ session_start(); session_destroy(); header('Location: '.url('index.php')); exit; ?>