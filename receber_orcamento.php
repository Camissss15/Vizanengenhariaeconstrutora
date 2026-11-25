<?php
require_once "config.php";
header("Content-Type: application/json; charset=utf-8");
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); echo json_encode(["erro"=>"Método não permitido."]); exit;
}
$nome=trim($_POST["nome"]??"");
$email=trim($_POST["email"]??"");
$telefone=trim($_POST["telefone"]??"");
$mensagem=trim($_POST["mensagem"]??"");
if($nome==="" || !filter_var($email,FILTER_VALIDATE_EMAIL) || $mensagem===""){
    http_response_code(400);echo json_encode(["erro"=>"Preencha nome, e-mail, assunto e mensagem."]);exit;
}
$s=$pdo->prepare("INSERT INTO orcamento (nome,email,telefone,assunto,mensagem) VALUES (?,?,?,?,?)");
$s->execute([$nome,$email,$telefone,$mensagem]);
http_response_code(201);echo json_encode(["mensagem"=>"Solicitação enviada com sucesso."]);
