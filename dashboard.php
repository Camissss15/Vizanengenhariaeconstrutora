<?php
require_once "auth.php";
exigir_login();
$nome = htmlspecialchars($_SESSION["usuario_nome"] ?? "Administrador", ENT_QUOTES, "UTF-8");
$tipo = htmlspecialchars($_SESSION["usuario_tipo"] ?? "Editor", ENT_QUOTES, "UTF-8");
?>
