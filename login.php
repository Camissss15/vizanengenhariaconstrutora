<?php
declare(strict_types=1);

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/auth.php";

const LOGIN_MAX_FALHAS = 5;
const LOGIN_BLOQUEIO_SEGUNDOS = 300;

function voltar_login(string $erro): void
{
    header("Location: vizanelogin.html?erro=" . $erro);
    exit;
}

function registrar_falha(): void
{
    $_SESSION["login_falhas"] = (int) ($_SESSION["login_falhas"] ?? 0) + 1;

    if ($_SESSION["login_falhas"] >= LOGIN_MAX_FALHAS) {
        $_SESSION["login_bloqueio"] = time() + LOGIN_BLOQUEIO_SEGUNDOS;
        $_SESSION["login_falhas"] = 0;
    }

    usleep(400000);
    voltar_login("credenciais");
}

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    header("Location: vizanelogin.html");
    exit;
}

if ((int) ($_SESSION["login_bloqueio"] ?? 0) > time()) {
    voltar_login("bloqueado");
}

$login = trim((string) ($_POST["login"] ?? $_POST["usuario"] ?? ""));
$senha = (string) ($_POST["senha"] ?? "");

if ($login === "" || $senha === "") {
    voltar_login("preencha");
}

try {
    $stmt = $pdo->prepare("
        SELECT Idu, usuario, senhausu, emailusu
        FROM usuario
        WHERE usuario = :usuario OR emailusu = :email
        LIMIT 1
    ");

    $stmt->execute([
        "usuario" => $login,
        "email" => $login
    ]);

    $usuario = $stmt->fetch();

    if (!$usuario) {
        registrar_falha();
    }

    $senhaBanco = (string) $usuario["senhausu"];
    $infoHash = password_get_info($senhaBanco);

    if (empty($infoHash["algo"])) {
        if (!hash_equals($senhaBanco, $senha)) {
            registrar_falha();
        }

        $atualizar = $pdo->prepare(
            "UPDATE usuario SET senhausu = :senha WHERE Idu = :id"
        );

        $atualizar->execute([
            "senha" => password_hash($senha, PASSWORD_DEFAULT),
            "id" => $usuario["Idu"]
        ]);

    } elseif (!password_verify($senha, $senhaBanco)) {
        registrar_falha();
    }

    session_regenerate_id(true);

    unset(
        $_SESSION["login_falhas"],
        $_SESSION["login_bloqueio"]
    );

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    $_SESSION["usuario_id"] = (int) $usuario["Idu"];
    $_SESSION["usuario_nome"] = $usuario["usuario"];
    $_SESSION["usuario_email"] = $usuario["emailusu"];

    header("Location: dashboard.php");
    exit;

} catch (PDOException $e) {
    error_log("Erro no login Vizan: " . $e->getMessage());
    http_response_code(500);
    exit("Ocorreu um erro ao realizar o login. Tente novamente.");
}
