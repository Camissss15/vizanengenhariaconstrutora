
<?php
declare(strict_types=1);

session_start();

require_once "config.php";

// Aceita somente requisições POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: vizanelogin.html");
    exit;
}

// Recebe os dados enviados pelo formulário
$login = trim($_POST["login"] ?? $_POST["usuario"] ?? "");
$senha = $_POST["senha"] ?? "";

// Verifica se os campos foram preenchidos
if ($login === "" || $senha === "") {
    header("Location: vizanelogin.html?erro=preencha");
    exit;
}

try {
    // Busca o usuário pelo nome ou e-mail
    // Os parâmetros possuem nomes diferentes para evitar erro no PDO.
    $sql = "
        SELECT Idu, usuario, senhausu, emailusu
        FROM usuario
        WHERE usuario = :usuario OR emailusu = :email
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        "usuario" => $login,
        "email" => $login
    ]);

    $usuario = $stmt->fetch();

    // Verifica se o usuário existe
    if (!$usuario) {
        header("Location: vizanelogin.html?erro=credenciais");
        exit;
    }

    $senhaBanco = (string) $usuario["senhausu"];

    // Verifica se a senha armazenada já está criptografada
    $infoHash = password_get_info($senhaBanco);

    if (empty($infoHash["algo"])) {
        // Senha antiga em texto simples: compara e atualiza para hash
        if (!hash_equals($senhaBanco, $senha)) {
            header("Location: vizanelogin.html?erro=credenciais");
            exit;
        }

        $novaSenha = password_hash($senha, PASSWORD_DEFAULT);

        $atualizar = $pdo->prepare("
            UPDATE usuario
            SET senhausu = :senha
            WHERE Idu = :id
        ");

        $atualizar->execute([
            "senha" => $novaSenha,
            "id" => $usuario["Idu"]
        ]);

    } else {
        // Senha já criptografada
        if (!password_verify($senha, $senhaBanco)) {
            header("Location: vizanelogin.html?erro=credenciais");
            exit;
        }
    }

    // Cria uma nova sessão após o login
    session_regenerate_id(true);

    $_SESSION["usuario_id"] = $usuario["Idu"];
    $_SESSION["usuario_nome"] = $usuario["usuario"];
    $_SESSION["usuario_email"] = $usuario["emailusu"];

    // Redireciona para o painel administrativo
    header("Location: dashboard.php");
    exit;

} catch (PDOException $e) {
    error_log("Erro no login Vizan: " . $e->getMessage());

    http_response_code(500);
    exit("Ocorreu um erro ao realizar o login. Tente novamente.");
}
