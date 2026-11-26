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

function corpo_json(): array {
    $dados = json_decode((string) file_get_contents("php://input"), true);
    return is_array($dados) ? $dados : [];
}

$acao = (string) ($_GET["acao"] ?? "");
$metodo = $_SERVER["REQUEST_METHOD"] ?? "GET";

$acoesPublicas = ["conteudo_publico", "imagens_publicas"];
$publica = in_array($acao, $acoesPublicas, true);

try {
    if (!$publica) {
        header("Cache-Control: no-store");

        if (empty($_SESSION["usuario_id"])) {
            resposta(["erro" => "Sessão expirada. Faça login novamente."], 401);
        }

        $existe = $pdo->prepare("SELECT 1 FROM usuario WHERE Idu = ?");
        $existe->execute([$_SESSION["usuario_id"]]);

        if (!$existe->fetchColumn()) {
            $_SESSION = [];
            resposta(["erro" => "Sessão expirada. Faça login novamente."], 401);
        }

        if ($metodo !== "GET") {
            validar_csrf();
        }
    }

    /* Conteúdo público */

    if ($acao === "conteudo_publico" && $metodo === "GET") {
        resposta(
            $pdo->query(
                "SELECT secao, descricao FROM conteudo_site ORDER BY id"
            )->fetchAll()
        );
    }

    if ($acao === "imagens_publicas" && $metodo === "GET") {
        resposta(
            $pdo->query(
                "SELECT id, caminho, descricao FROM imagem_site
                 ORDER BY data_cadastro DESC, id DESC"
            )->fetchAll()
        );
    }

    /* Resumo */

    if ($acao === "resumo" && $metodo === "GET") {
        $total = (int) $pdo->query(
            "SELECT COUNT(*) FROM orcamento"
        )->fetchColumn();

        $pendentes = (int) $pdo->query(
            "SELECT COUNT(*) FROM orcamento WHERE status <> 'Respondido'"
        )->fetchColumn();

        $imagens = (int) $pdo->query(
            "SELECT COUNT(*) FROM imagem_site"
        )->fetchColumn();

        $usuarios = (int) $pdo->query(
            "SELECT COUNT(*) FROM usuario"
        )->fetchColumn();

        $recentes = $pdo->query(
            "SELECT id,nome,email,assunto,data_envio,status
             FROM orcamento
             ORDER BY data_envio DESC, id DESC
             LIMIT 5"
        )->fetchAll();

        resposta(compact(
            "total",
            "pendentes",
            "imagens",
            "usuarios",
            "recentes"
        ));
    }

    /* Orçamentos */

    if ($acao === "orcamentos" && $metodo === "GET") {
        resposta(
            $pdo->query(
                "SELECT id,nome,email,telefone,assunto,mensagem,status,data_envio
                 FROM orcamento
                 ORDER BY data_envio DESC, id DESC"
            )->fetchAll()
        );
    }

    if ($acao === "orcamento_status" && $metodo === "POST") {
        $d = corpo_json();

        $id = (int) ($d["id"] ?? 0);
        $status = (string) ($d["status"] ?? "");

        if (
            $id < 1 ||
            !in_array($status, ["Pendente", "Em análise", "Respondido"], true)
        ) {
            resposta(["erro" => "Solicitação ou status inválido."], 400);
        }

        $s = $pdo->prepare(
            "UPDATE orcamento SET status = ? WHERE id = ?"
        );

        $s->execute([$status, $id]);

        resposta(["mensagem" => "Status atualizado."]);
    }

    if ($acao === "orcamento_excluir" && $metodo === "POST") {
        $d = corpo_json();
        $id = (int) ($d["id"] ?? 0);

        if ($id < 1) {
            resposta(["erro" => "Solicitação inválida."], 400);
        }

        $s = $pdo->prepare(
            "DELETE FROM orcamento WHERE id = ?"
        );

        $s->execute([$id]);

        resposta(["mensagem" => "Solicitação excluída."]);
    }

    /* Conteúdo do site */

    if ($acao === "conteudo" && $metodo === "GET") {
        resposta(
            $pdo->query(
                "SELECT secao, descricao FROM conteudo_site ORDER BY id"
            )->fetchAll()
        );
    }

    if ($acao === "conteudo" && $metodo === "POST") {
        $d = corpo_json();

        $secao = trim((string) ($d["secao"] ?? ""));
        $descricao = trim((string) ($d["descricao"] ?? ""));

        $permitidas = [
            "Quem Somos",
            "Serviços",
            "Projetos",
            "Onde Estamos"
        ];

        if (!in_array($secao, $permitidas, true) || $descricao === "") {
            resposta([
                "erro" => "Selecione uma seção válida e informe a descrição."
            ], 400);
        }

        if (mb_strlen($descricao) > 5000) {
            resposta([
                "erro" => "A descrição deve ter no máximo 5.000 caracteres."
            ], 400);
        }

        $s = $pdo->prepare(
            "INSERT INTO conteudo_site (secao, descricao)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE descricao = VALUES(descricao)"
        );

        $s->execute([$secao, $descricao]);

        resposta([
            "mensagem" => "Descrição salva. Ela já aparece no site."
        ]);
    }

    /* Imagens */

    if ($acao === "imagens" && $metodo === "GET") {
        resposta(
            $pdo->query(
                "SELECT id,caminho,descricao,data_cadastro
                 FROM imagem_site
                 ORDER BY data_cadastro DESC, id DESC"
            )->fetchAll()
        );
    }

    if ($acao === "imagem_upload" && $metodo === "POST") {
        if (!isset($_FILES["imagem"])) {
            resposta(["erro" => "Selecione uma imagem válida."], 400);
        }

        $f = $_FILES["imagem"];

        if (
            $f["error"] === UPLOAD_ERR_INI_SIZE ||
            $f["error"] === UPLOAD_ERR_FORM_SIZE
        ) {
            resposta([
                "erro" => "A imagem é maior que o limite permitido pelo servidor."
            ], 400);
        }

        if ($f["error"] !== UPLOAD_ERR_OK) {
            resposta(["erro" => "Selecione uma imagem válida."], 400);
        }

        $descricao = trim((string) ($_POST["descricao"] ?? ""));

        if ($descricao === "") {
            resposta(["erro" => "Informe a descrição da imagem."], 400);
        }

        if (mb_strlen($descricao) > 180) {
            resposta([
                "erro" => "A descrição deve ter no máximo 180 caracteres."
            ], 400);
        }

        if ($f["size"] > 5 * 1024 * 1024) {
            resposta([
                "erro" => "A imagem deve ter no máximo 5 MB."
            ], 400);
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f["tmp_name"]);

        $extensoes = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];

        if (
            !isset($extensoes[$mime]) ||
            @getimagesize($f["tmp_name"]) === false
        ) {
            resposta([
                "erro" => "Formato inválido. Use JPG, PNG ou WEBP."
            ], 400);
        }

        $pasta = __DIR__ . "/uploads";

        if (
            !is_dir($pasta) &&
            !mkdir($pasta, 0755, true) &&
            !is_dir($pasta)
        ) {
            resposta([
                "erro" => "Não foi possível criar a pasta de imagens."
            ], 500);
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . "." . $extensoes[$mime];

        if (!move_uploaded_file($f["tmp_name"], $pasta . "/" . $nomeArquivo)) {
            resposta([
                "erro" => "Não foi possível salvar a imagem."
            ], 500);
        }

        $caminho = "uploads/" . $nomeArquivo;

        try {
            $s = $pdo->prepare(
                "INSERT INTO imagem_site (caminho, descricao)
                 VALUES (?, ?)"
            );

            $s->execute([$caminho, $descricao]);

        } catch (Throwable $e) {
            @unlink($pasta . "/" . $nomeArquivo);
            throw $e;
        }

        resposta([
            "mensagem" => "Imagem enviada. Ela já aparece em Projetos no site.",
            "id" => (int) $pdo->lastInsertId(),
            "caminho" => $caminho,
            "descricao" => $descricao
        ], 201);
    }

    if ($acao === "imagem_excluir" && $metodo === "POST") {
        $d = corpo_json();
        $id = (int) ($d["id"] ?? 0);

        $s = $pdo->prepare(
            "SELECT caminho FROM imagem_site WHERE id = ?"
        );

        $s->execute([$id]);
        $img = $s->fetch();

        if (!$img) {
            resposta(["erro" => "Imagem não encontrada."], 404);
        }

        $del = $pdo->prepare(
            "DELETE FROM imagem_site WHERE id = ?"
        );

        $del->execute([$id]);

        $arquivo = __DIR__ . "/uploads/" .
            basename((string) $img["caminho"]);

        if (is_file($arquivo)) {
            @unlink($arquivo);
        }

        resposta(["mensagem" => "Imagem excluída."]);
    }

    /* Usuários */

    if ($acao === "usuarios" && $metodo === "GET") {
        resposta(
            $pdo->query(
                "SELECT Idu, usuario, emailusu
                 FROM usuario
                 ORDER BY usuario"
            )->fetchAll()
        );
    }

    if ($acao === "usuario_criar" && $metodo === "POST") {
        $d = corpo_json();

        $nome = trim((string) ($d["nome"] ?? ""));
        $email = trim((string) ($d["email"] ?? ""));
        $senha = (string) ($d["senha"] ?? "");

        if (
            $nome === "" ||
            mb_strlen($nome) > 120 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            mb_strlen($email) > 150 ||
            strlen($senha) < 8
        ) {
            resposta([
                "erro" => "Informe usuário, e-mail válido e senha com pelo menos 8 caracteres."
            ], 400);
        }

        $dup = $pdo->prepare(
            "SELECT 1 FROM usuario
             WHERE usuario = ? OR emailusu = ?
             LIMIT 1"
        );

        $dup->execute([$nome, $email]);

        if ($dup->fetchColumn()) {
            resposta(["erro" => "Usuário ou e-mail já cadastrado."], 409);
        }

        $s = $pdo->prepare(
            "INSERT INTO usuario (usuario, senhausu, emailusu)
             VALUES (?, ?, ?)"
        );

        try {
            $s->execute([
                $nome,
                password_hash($senha, PASSWORD_DEFAULT),
                $email
            ]);

        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                resposta([
                    "erro" => "Usuário ou e-mail já cadastrado."
                ], 409);
            }

            throw $e;
        }

        resposta(["mensagem" => "Usuário cadastrado."], 201);
    }

    if ($acao === "usuario_excluir" && $metodo === "POST") {
        $d = corpo_json();
        $id = (int) ($d["id"] ?? 0);

        if ($id < 1 || $id === (int) $_SESSION["usuario_id"]) {
            resposta([
                "erro" => "Não é possível excluir a sua própria conta."
            ], 400);
        }

        $s = $pdo->prepare(
            "DELETE FROM usuario WHERE Idu = ?"
        );

        $s->execute([$id]);

        resposta(["mensagem" => "Usuário excluído."]);
    }

    /* Perfil */

    if ($acao === "perfil" && $metodo === "POST") {
        $d = corpo_json();

        $nome = trim((string) ($d["nome"] ?? ""));
        $email = trim((string) ($d["email"] ?? ""));

        if (
            $nome === "" ||
            mb_strlen($nome) > 120 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            mb_strlen($email) > 150
        ) {
            resposta([
                "erro" => "Informe usuário e e-mail válido."
            ], 400);
        }

        $dup = $pdo->prepare(
            "SELECT 1 FROM usuario
             WHERE (usuario = ? OR emailusu = ?)
             AND Idu <> ?
             LIMIT 1"
        );

        $dup->execute([
            $nome,
            $email,
            $_SESSION["usuario_id"]
        ]);

        if ($dup->fetchColumn()) {
            resposta([
                "erro" => "Este usuário ou e-mail já está em uso."
            ], 409);
        }

        $s = $pdo->prepare(
            "UPDATE usuario
             SET usuario = ?, emailusu = ?
             WHERE Idu = ?"
        );

        try {
            $s->execute([
                $nome,
                $email,
                $_SESSION["usuario_id"]
            ]);

        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                resposta([
                    "erro" => "Este usuário ou e-mail já está em uso."
                ], 409);
            }

            throw $e;
        }

        $_SESSION["usuario_nome"] = $nome;
        $_SESSION["usuario_email"] = $email;

        resposta(["mensagem" => "Perfil atualizado."]);
    }

    /* Senha */

    if ($acao === "senha" && $metodo === "POST") {
        $d = corpo_json();

        $atual = (string) ($d["atual"] ?? "");
        $nova = (string) ($d["nova"] ?? "");

        if (strlen($nova) < 8) {
            resposta([
                "erro" => "A nova senha deve ter pelo menos 8 caracteres."
            ], 400);
        }

        $s = $pdo->prepare(
            "SELECT senhausu FROM usuario WHERE Idu = ?"
        );

        $s->execute([$_SESSION["usuario_id"]]);

        $hash = (string) $s->fetchColumn();

        if ($hash === "" || !password_verify($atual, $hash)) {
            resposta(["erro" => "A senha atual não confere."], 400);
        }

        $u = $pdo->prepare(
            "UPDATE usuario SET senhausu = ? WHERE Idu = ?"
        );

        $u->execute([
            password_hash($nova, PASSWORD_DEFAULT),
            $_SESSION["usuario_id"]
        ]);

        session_regenerate_id(true);

        resposta(["mensagem" => "Senha alterada com sucesso."]);
    }

    resposta(["erro" => "Ação não encontrada."], 404);

} catch (Throwable $e) {
    error_log("Vizan API: " . $e->getMessage());

    resposta([
        "erro" => "Ocorreu um erro interno. Confira os registros do servidor."
    ], 500);
}
