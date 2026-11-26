<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off");
    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => $https,
        "httponly" => true,
        "samesite" => "Lax"
    ]);
    session_start();
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
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

function validar_csrf(): void {
    $token = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
    if (!$token || !hash_equals($_SESSION["csrf_token"] ?? "", $token)) {
        http_response_code(419);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["erro" => "Sessão expirada. Atualize a página e tente novamente."], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
