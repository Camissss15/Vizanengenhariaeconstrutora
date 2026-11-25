<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function exigir_login(): void {
    if (empty($_SESSION["usuario_id"])) {
        header("Location: vizanelogin.html");
        exit;
    }
}

function exigir_admin(): void {
    exigir_login();
    if (($_SESSION["usuario_tipo"] ?? "") !== "Administrador") {
        http_response_code(403);
        exit("Você não tem permissão para realizar esta ação.");
    }
}
