<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (
        !empty($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off"
    );

    ini_set("session.use_strict_mode", "1");

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

function exigir_login(): void
{
    if (empty($_SESSION["usuario_id"])) {
        header("Location: vizanelogin.html");
        exit;
    }
}

function exigir_admin(): void
{
    exigir_login();
}

function validar_csrf(): void
{
    $token = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
    $sessao = $_SESSION["csrf_token"] ?? "";

    if (
        $token === "" ||
        $sessao === "" ||
        !hash_equals($sessao, $token)
    ) {
        http_response_code(419);

        header("Content-Type: application/json; charset=utf-8");

        echo json_encode([
            "erro" => "Sessão expirada. Atualize a página e tente novamente."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}
