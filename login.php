<?php
require_once "config.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: vizanelogin.html");
    exit;
}

$login = trim($_POST["login"] ?? "");
$senha = $_POST["senha"] ?? "";

if ($login === "" || $senha === "") {
    header("Location: vizanelogin.html?erro=1");
    exit;
}

$stmt = $pdo->prepare("SELECT Idu, usuario, senha, emailusu, FROM usuario WHERE nomeusu = :login OR emailusu = :login LIMIT 1");
$stmt->execute(["login" => $login]);
$usuario = $stmt->fetch();

if ($usuario && password_verify($senha, $usuario["senha"])) {
    session_regenerate_id(true);
    $_SESSION["usuario_id"] = (int)$usuario["Idu"];
    $_SESSION["usuario_nome"] = $usuario["usuario"];
    $_SESSION["usuario_email"] = $usuario["emailusu"];
    $_SESSION["usuario_senha"] = $senha["senha"];
    header("Location: dashboard.php");
    exit;
}

header("Location: vizanelogin.html?erro=1");
exit;
