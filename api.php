<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/auth.php";

header("Content-Type: application/json; charset=utf-8");

function resposta(array $dados, int $status = 200): void {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function admin(): void { exigir_admin(); }

$acao = $_GET["acao"] ?? "";
$metodo = $_SERVER["REQUEST_METHOD"] ?? "GET";

/* Conteúdo e imagens publicados podem ser consultados pelo site público. */
$acoesPublicas = ["conteudo_publico", "imagens_publicas"];
if (!in_array($acao, $acoesPublicas, true)) {
    exigir_login();
}
if ($metodo !== "GET" && !in_array($acao, $acoesPublicas, true)) {
    validar_csrf();
}

try {
    if ($acao === "conteudo_publico" && $metodo === "GET") {
        resposta($pdo->query("SELECT secao, descricao FROM conteudo_site ORDER BY id")->fetchAll());
    }

    if ($acao === "imagens_publicas" && $metodo === "GET") {
        resposta($pdo->query("SELECT id, caminho, descricao FROM imagem_site ORDER BY data_cadastro DESC")->fetchAll());
    }

    if ($acao === "resumo" && $metodo === "GET") {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM orcamento")->fetchColumn();
        $pendentes = (int)$pdo->query("SELECT COUNT(*) FROM orcamento WHERE status <> 'Respondido'")->fetchColumn();
        $imagens = (int)$pdo->query("SELECT COUNT(*) FROM imagem_site")->fetchColumn();
        $usuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
        $recentes = $pdo->query("SELECT id,nome,email,assunto,data_envio,status FROM orcamento ORDER BY data_envio DESC LIMIT 5")->fetchAll();
        resposta(compact("total", "pendentes", "imagens", "usuarios", "recentes"));
    }

    if ($acao === "orcamentos" && $metodo === "GET") {
        resposta($pdo->query("SELECT id,nome,email,telefone,assunto,mensagem,status,data_envio FROM orcamento ORDER BY data_envio DESC")->fetchAll());
    }

    if ($acao === "orcamento_status" && $metodo === "POST") {
        admin();
        $d = json_decode(file_get_contents("php://input"), true) ?? [];
        $id = (int)($d["id"] ?? 0);
        $status = (string)($d["status"] ?? "");
        if ($id < 1 || !in_array($status, ["Pendente", "Em análise", "Respondido"], true)) {
            resposta(["erro" => "Solicitação ou status inválido."], 400);
        }
        $s = $pdo->prepare("UPDATE orcamento SET status=? WHERE id=?");
        $s->execute([$status, $id]);
        resposta(["mensagem" => "Status atualizado."]);
    }

    if ($acao === "orcamento_excluir" && $metodo === "POST") {
        admin();
        $d = json_decode(file_get_contents("php://input"), true) ?? [];
        $id = (int)($d["id"] ?? 0);
        if ($id < 1) resposta(["erro" => "Solicitação inválida."], 400);
        $s = $pdo->prepare("DELETE FROM orcamento WHERE id=?");
        $s->execute([$id]);
        resposta(["mensagem" => "Solicitação excluída."]);
    }

    if ($acao === "conteudo" && $metodo === "GET") {
        resposta($pdo->query("SELECT secao, descricao FROM conteudo_site ORDER BY id")->fetchAll());
    }

    if ($acao === "conteudo" && $metodo === "POST") {
        admin();
        $d = json_decode(file_get_contents("php://input"), true) ?? [];
        $secao = trim((string)($d["secao"] ?? ""));
        $descricao = trim((string)($d["descricao"] ?? ""));
        $permitidas = ["Quem Somos", "Serviços", "Projetos", "Onde Estamos"];
        if (!in_array($secao, $permitidas, true) || $descricao === "") {
            resposta(["erro" => "Selecione uma seção válida e informe a descrição."], 400);
        }
        $s = $pdo->prepare("INSERT INTO conteudo_site (secao,descricao) VALUES (?,?) ON DUPLICATE KEY UPDATE descricao=VALUES(descricao)");
        $s->execute([$secao, $descricao]);
        resposta(["mensagem" => "Descrição salva."]);
    }

    if ($acao === "imagens" && $metodo === "GET") {
        resposta($pdo->query("SELECT id,caminho,descricao,data_cadastro FROM imagem_site ORDER BY data_cadastro DESC")->fetchAll());
    }

    if ($acao === "imagem_upload" && $metodo === "POST") {
        admin();
        if (!isset($_FILES["imagem"]) || $_FILES["imagem"]["error"] !== UPLOAD_ERR_OK) {
            resposta(["erro" => "Selecione uma imagem válida."], 400);
        }
        $descricao = trim((string)($_POST["descricao"] ?? ""));
        $f = $_FILES["imagem"];
        if ($descricao === "") resposta(["erro" => "Informe a descrição da imagem."], 400);
        if ($f["size"] > 5 * 1024 * 1024) resposta(["erro" => "A imagem deve ter no máximo 5 MB."], 400);

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f["tmp_name"]);
        $extensoes = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
        if (!isset($extensoes[$mime]) || @getimagesize($f["tmp_name"]) === false) {
            resposta(["erro" => "Formato inválido. Use JPG, PNG ou WEBP."], 400);
        }

        $pasta = __DIR__ . "/uploads";
        if (!is_dir($pasta) && !mkdir($pasta, 0755, true) && !is_dir($pasta)) {
            resposta(["erro" => "Não foi possível criar a pasta de imagens."], 500);
        }
        $nomeArquivo = bin2hex(random_bytes(16)) . "." . $extensoes[$mime];
        if (!move_uploaded_file($f["tmp_name"], $pasta . "/" . $nomeArquivo)) {
            resposta(["erro" => "Não foi possível salvar a imagem."], 500);
        }
        $caminho = "uploads/" . $nomeArquivo;
        try {
            $s = $pdo->prepare("INSERT INTO imagem_site (caminho,descricao) VALUES (?,?)");
            $s->execute([$caminho, $descricao]);
        } catch (Throwable $e) {
            @unlink($pasta . "/" . $nomeArquivo);
            throw $e;
        }
        resposta(["mensagem" => "Imagem enviada.", "id" => (int)$pdo->lastInsertId(), "caminho" => $caminho, "descricao" => $descricao], 201);
    }

    if ($acao === "imagem_excluir" && $metodo === "POST") {
        admin();
        $d = json_decode(file_get_contents("php://input"), true) ?? [];
        $id = (int)($d["id"] ?? 0);
        $s = $pdo->prepare("SELECT caminho FROM imagem_site WHERE id=?");
        $s->execute([$id]);
        $img = $s->fetch();
        if (!$img) resposta(["erro" => "Imagem não encontrada."], 404);
        $del = $pdo->prepare("DELETE FROM imagem_site WHERE id=?");
        $del->execute([$id]);
        $arquivo = __DIR__ . "/" . $img["caminho"];
        if (is_file($arquivo)) @unlink($arquivo);
        resposta(["mensagem" => "Imagem excluída."]);
    }

    if ($acao === "usuarios" && $metodo === "GET") {
        admin();
        resposta($pdo->query("SELECT Idu,nomeusu,loginusu,emailusu,tipousu FROM usuario ORDER BY nomeusu")->fetchAll());
    }

    if ($acao === "usuario_criar" && $metodo === "POST") {
        admin();
        $d = json_decode(file_get_contents("php://input"), true) ?? [];
        $nome = trim((string)($d["nome"] ?? ""));
        $email = trim((string)($d["email"] ?? ""));
        $login = trim((string)($d["login"] ?? ""));
        $senha = (string)($d["senha"] ?? "");
        $tipo = (string)($d["tipo"] ?? "Editor");
        if ($nome === "" || mb_strlen($nome) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            mb_strlen($email) > 150 || $login === "" || mb_strlen($login) > 60 || strlen($senha) < 8) {
            resposta(["erro" => "Informe nome, login, e-mail válido e senha com pelo menos 8 caracteres."], 400);
        }
        if (!in_array($tipo, ["Editor", "Administrador"], true)) resposta(["erro" => "Perfil inválido."], 400);
        $s = $pdo->prepare("INSERT INTO usuario (nomeusu,loginusu,senhausu,emailusu,tipousu) VALUES (?,?,?,?,?)");
        try {
            $s->execute([$nome, $login, password_hash($senha, PASSWORD_DEFAULT), $email, $tipo]);
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") resposta(["erro" => "Login ou e-mail já cadastrado."], 409);
            throw $e;
        }
        resposta(["mensagem" => "Usuário cadastrado."], 201);
    }

    if ($acao === "usuario_excluir" && $metodo === "POST") {
        admin();
        $d = json_decode(file_get_contents("php://input"), true) ?? [];
        $id = (int)($d["id"] ?? 0);
        if ($id < 1 || $id === (int)$_SESSION["usuario_id"]) resposta(["erro" => "Não é possível excluir esta conta."], 400);
        $check = $pdo->query("SELECT COUNT(*) FROM usuario WHERE tipousu='Administrador'")->fetchColumn();
        $target = $pdo->prepare("SELECT tipousu FROM usuario WHERE Idu=?");
        $target->execute([$id]);
        $tipoAlvo = $target->fetchColumn();
        if ($tipoAlvo === "Administrador" && (int)$check <= 1) resposta(["erro" => "Não é possível excluir o último administrador."], 400);
        $s = $pdo->prepare("DELETE FROM usuario WHERE Idu=?");
        $s->execute([$id]);
        resposta(["mensagem" => "Usuário excluído."]);
    }

    if ($acao === "perfil" && $metodo === "POST") {
        $d = json_decode(file_get_contents("php://input"), true) ?? [];
        $nome = trim((string)($d["nome"] ?? ""));
        $email = trim((string)($d["email"] ?? ""));
        if ($nome === "" || mb_strlen($nome) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            resposta(["erro" => "Informe nome e e-mail válido."], 400);
        }
        $s = $pdo->prepare("UPDATE usuario SET nomeusu=?,emailusu=? WHERE Idu=?");
        try {
            $s->execute([$nome, $email, $_SESSION["usuario_id"]]);
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") resposta(["erro" => "Este e-mail já está em uso."], 409);
            throw $e;
        }
        $_SESSION["usuario_nome"] = $nome;
        $_SESSION["usuario_email"] = $email;
        resposta(["mensagem" => "Perfil atualizado."]);
    }

    if ($acao === "senha" && $metodo === "POST") {
        $d = json_decode(file_get_contents("php://input"), true) ?? [];
        $atual = (string)($d["atual"] ?? "");
        $nova = (string)($d["nova"] ?? "");
        if (strlen($nova) < 8) resposta(["erro" => "A nova senha deve ter pelo menos 8 caracteres."], 400);
        $s = $pdo->prepare("SELECT senhausu FROM usuario WHERE Idu=?");
        $s->execute([$_SESSION["usuario_id"]]);
        $hash = $s->fetchColumn();
        if (!$hash || !password_verify($atual, $hash)) resposta(["erro" => "A senha atual não confere."], 400);
        $u = $pdo->prepare("UPDATE usuario SET senhausu=? WHERE Idu=?");
        $u->execute([password_hash($nova, PASSWORD_DEFAULT), $_SESSION["usuario_id"]]);
        session_regenerate_id(true);
        resposta(["mensagem" => "Senha alterada com sucesso."]);
    }

    resposta(["erro" => "Ação não encontrada."], 404);

} catch (Throwable $e) {
    error_log("Vizan API: " . $e->getMessage());
    resposta(["erro" => "Ocorreu um erro interno. Confira os registros do servidor."], 500);
}
