<?php
declare(strict_types=1);

// Configuração e inicialização da sessão.
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (
        !empty($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off"
    );

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => $https,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

// Token de segurança CSRF.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Verifica se o usuário está autenticado.
function exigir_login(): void
{
    if (empty($_SESSION["usuario_id"])) {
        header("Location: vizanelogin.html");
        exit;
    }
}

// Como não existe nível de acesso no banco,
// todos os usuários autenticados têm as mesmas permissões.
function exigir_admin(): void
{
    exigir_login();
}

// Validação de segurança para requisições POST.
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
