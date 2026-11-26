<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: vizanelogin.html");
    exit;
}

$login = trim($_POST["login"] ?? "");
$senha = (string)($_POST["senha"] ?? "");

if ($login === "" || $senha === "") {
    header("Location: vizanelogin.html?erro=1");
    exit;
}

$stmt = $pdo->prepare(
    "SELECT Idu, nomeusu, loginusu, senhausu, emailusu, tipousu
     FROM usuario
     WHERE loginusu = :login OR emailusu = :login
     LIMIT 1"
);
$stmt->execute(["login" => $login]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($senha, $usuario["senhausu"])) {
    usleep(300000);
    header("Location: vizanelogin.html?erro=1");
    exit;
}

session_regenerate_id(true);
$_SESSION["usuario_id"] = (int)$usuario["Idu"];
$_SESSION["usuario_nome"] = $usuario["nomeusu"];
$_SESSION["usuario_login"] = $usuario["loginusu"];
$_SESSION["usuario_email"] = $usuario["emailusu"];
$_SESSION["usuario_tipo"] = $usuario["tipousu"];
$_SESSION["csrf_token"] = bin2hex(random_bytes(32));

header("Location: dashboard.php");
exit;
