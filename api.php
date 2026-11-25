<?php
require_once "config.php";
require_once "auth.php";
exigir_login();

header("Content-Type: application/json; charset=utf-8");
function resposta($dados, int $status=200): void {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}
function admin(): void { exigir_admin(); }
$acao = $_GET["acao"] ?? "";
$metodo = $_SERVER["REQUEST_METHOD"];

try {
    if ($acao === "resumo" && $metodo === "GET") {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM orcamento")->fetchColumn();
        $pendentes = (int)$pdo->query("SELECT COUNT(*) FROM orcamento WHERE status <> 'Respondido'")->fetchColumn();
        $imagens = (int)$pdo->query("SELECT COUNT(*) FROM imagem_site")->fetchColumn();
        $usuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
        $recentes = $pdo->query("SELECT id, nome, email, assunto, data_envio, status FROM orcamento ORDER BY data_envio DESC LIMIT 5")->fetchAll();
        resposta(compact("total","pendentes","imagens","usuarios","recentes"));
    }

    if ($acao === "orcamentos" && $metodo === "GET") {
        resposta($pdo->query("SELECT * FROM orcamento ORDER BY data_envio DESC")->fetchAll());
    }
    if ($acao === "orcamento_status" && $metodo === "POST") {
        admin();
        $d = json_decode(file_get_contents("php://input"), true) ?? [];
        $id = (int)($d["id"] ?? 0);
        $status = $d["status"] ?? "";
        if (!in_array($status, ["Pendente","Em análise","Respondido"], true)) resposta(["erro"=>"Status inválido."],400);
        $s = $pdo->prepare("UPDATE orcamento SET status=? WHERE id=?");
        $s->execute([$status,$id]);
        resposta(["mensagem"=>"Status atualizado."]);
    }

    if ($acao === "conteudo" && $metodo === "GET") {
        resposta($pdo->query("SELECT secao, descricao FROM conteudo_site")->fetchAll());
    }
    if ($acao === "conteudo" && $metodo === "POST") {
        admin();
        $d = json_decode(file_get_contents("php://input"), true) ?? [];
        $secao = trim($d["secao"] ?? "");
        $descricao = trim($d["descricao"] ?? "");
        if ($secao==="" || $descricao==="") resposta(["erro"=>"Preencha a seção e a descrição."],400);
        $s=$pdo->prepare("INSERT INTO conteudo_site (secao,descricao) VALUES (?,?) ON DUPLICATE KEY UPDATE descricao=VALUES(descricao)");
        $s->execute([$secao,$descricao]);
        resposta(["mensagem"=>"Descrição salva."]);
    }

    if ($acao === "imagens" && $metodo === "GET") {
        resposta($pdo->query("SELECT id, caminho, descricao, data_cadastro FROM imagem_site ORDER BY data_cadastro DESC")->fetchAll());
    }
    if ($acao === "imagem_upload" && $metodo === "POST") {
        admin();
        if (empty($_FILES["imagem"]) || $_FILES["imagem"]["error"] !== UPLOAD_ERR_OK) resposta(["erro"=>"Selecione uma imagem válida."],400);
        $descricao=trim($_POST["descricao"] ?? "");
        if ($descricao==="") resposta(["erro"=>"Informe a descrição da imagem."],400);
        $f=$_FILES["imagem"];
        if ($f["size"] > 5*1024*1024) resposta(["erro"=>"A imagem deve ter no máximo 5 MB."],400);
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f["tmp_name"]);
        $extensoes=["image/jpeg"=>"jpg","image/png"=>"png","image/webp"=>"webp"];
        if (!isset($extensoes[$mime])) resposta(["erro"=>"Formato não permitido. Use JPG, PNG ou WEBP."],400);
        $pasta=__DIR__."/uploads";
        if (!is_dir($pasta)) mkdir($pasta,0755,true);
        $nome=bin2hex(random_bytes(16)).".".$extensoes[$mime];
        if (!move_uploaded_file($f["tmp_name"],$pasta."/".$nome)) resposta(["erro"=>"Não foi possível salvar a imagem."],500);
        $caminho="uploads/".$nome;
        $s=$pdo->prepare("INSERT INTO imagem_site (caminho,descricao) VALUES (?,?)");
        $s->execute([$caminho,$descricao]);
        resposta(["mensagem"=>"Imagem enviada.","id"=>(int)$pdo->lastInsertId(),"caminho"=>$caminho,"descricao"=>$descricao],201);
    }
    if ($acao === "imagem_excluir" && $metodo === "POST") {
        admin();
        $d=json_decode(file_get_contents("php://input"),true)??[];
        $id=(int)($d["id"]??0);
        $s=$pdo->prepare("SELECT caminho FROM imagem_site WHERE id=?");$s->execute([$id]);$img=$s->fetch();
        if (!$img) resposta(["erro"=>"Imagem não encontrada."],404);
        $del=$pdo->prepare("DELETE FROM imagem_site WHERE id=?");$del->execute([$id]);
        $arquivo=__DIR__."/".$img["caminho"];if (is_file($arquivo)) unlink($arquivo);
        resposta(["mensagem"=>"Imagem excluída."]);
    }

    if ($acao === "usuarios" && $metodo === "GET") {
        admin();
        resposta($pdo->query("SELECT Idu,nomeusu,emailusu,tipousu FROM usuario ORDER BY nomeusu")->fetchAll());
    }
    if ($acao === "usuario_criar" && $metodo === "POST") {
        admin();
        $d=json_decode(file_get_contents("php://input"),true)??[];
        $nome=trim($d["nome"]??"");$email=trim($d["email"]??"");$login=trim($d["login"]??"");$senha=$d["senha"]??"";$tipo=$d["tipo"]??"Editor";
        if ($nome==="" || !filter_var($email,FILTER_VALIDATE_EMAIL) || $login==="" || strlen($senha)<8) resposta(["erro"=>"Informe nome, login, e-mail válido e senha com pelo menos 8 caracteres."],400);
        if (!in_array($tipo,["Editor","Administrador"],true)) resposta(["erro"=>"Perfil inválido."],400);
        $s=$pdo->prepare("INSERT INTO usuario (nomeusu,loginusu,senhausu,emailusu,tipousu) VALUES (?,?,?,?,?)");
        try{$s->execute([$nome,$login,password_hash($senha,PASSWORD_DEFAULT),$email,$tipo]);}
        catch(PDOException $e){resposta(["erro"=>"Login ou e-mail já cadastrado."],409);}
        resposta(["mensagem"=>"Usuário cadastrado."],201);
    }
    if ($acao === "usuario_excluir" && $metodo === "POST") {
        admin();
        $d=json_decode(file_get_contents("php://input"),true)??[];$id=(int)($d["id"]??0);
        if ($id===(int)$_SESSION["usuario_id"]) resposta(["erro"=>"Você não pode excluir a própria conta."],400);
        $s=$pdo->prepare("DELETE FROM usuario WHERE Idu=?");$s->execute([$id]);
        resposta(["mensagem"=>"Usuário excluído."]);
    }
    if ($acao === "perfil" && $metodo === "POST") {
        $d=json_decode(file_get_contents("php://input"),true)??[];
        $nome=trim($d["nome"]??"");$email=trim($d["email"]??"");
        if($nome==="" || !filter_var($email,FILTER_VALIDATE_EMAIL)) resposta(["erro"=>"Informe nome e e-mail válido."],400);
        $s=$pdo->prepare("UPDATE usuario SET nomeusu=?,emailusu=? WHERE Idu=?");
        try{$s->execute([$nome,$email,$_SESSION["usuario_id"]]);}catch(PDOException $e){resposta(["erro"=>"Este e-mail já está em uso."],409);}
        $_SESSION["usuario_nome"]=$nome;$_SESSION["usuario_email"]=$email;
        resposta(["mensagem"=>"Perfil atualizado."]);
    }

    resposta(["erro"=>"Ação não encontrada."],404);
} catch (PDOException $e) {
    resposta(["erro"=>"Erro ao consultar o banco de dados. Confira se o SQL foi importado."],500);
}
