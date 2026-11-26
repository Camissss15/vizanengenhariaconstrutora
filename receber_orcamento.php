<?php
declare(strict_types=1);

require_once __DIR__ . "/config.php";

header("Content-Type: application/json; charset=utf-8");

function resposta_orcamento(array $dados, int $status = 200): void {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    resposta_orcamento(["erro" => "Método não permitido."], 405);
}

if (!empty($_POST["website"] ?? "")) {
    resposta_orcamento(["mensagem" => "Solicitação recebida."], 201);
}

$nome = trim((string)($_POST["nome"] ?? ""));
$email = trim((string)($_POST["email"] ?? ""));
$telefone = trim((string)($_POST["telefone"] ?? ""));
$assunto = trim((string)($_POST["assunto"] ?? ""));
$mensagem = trim((string)($_POST["mensagem"] ?? ""));

if (
    $nome === "" ||
    mb_strlen($nome) > 120 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    mb_strlen($email) > 150 ||
    $assunto === "" ||
    mb_strlen($assunto) > 150 ||
    $mensagem === "" ||
    mb_strlen($mensagem) > 10000 ||
    mb_strlen($telefone) > 30
) {
    resposta_orcamento([
        "erro" => "Confira os campos obrigatórios e tente novamente."
    ], 400);
}

try {
    $dup = $pdo->prepare(
        "SELECT 1 FROM orcamento
         WHERE email = :email
         AND assunto = :assunto
         AND mensagem = :mensagem
         AND data_envio > (NOW() - INTERVAL 10 MINUTE)
         LIMIT 1"
    );

    $dup->execute([
        "email" => $email,
        "assunto" => $assunto,
        "mensagem" => $mensagem
    ]);

    if ($dup->fetchColumn()) {
        resposta_orcamento([
            "mensagem" => "Sua solicitação foi enviada com sucesso. Nossa equipe entrará em contato."
        ], 201);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO orcamento
        (nome, email, telefone, assunto, mensagem)
        VALUES
        (:nome, :email, :telefone, :assunto, :mensagem)"
    );

    $stmt->execute([
        "nome" => $nome,
        "email" => $email,
        "telefone" => $telefone !== "" ? $telefone : null,
        "assunto" => $assunto,
        "mensagem" => $mensagem
    ]);

    resposta_orcamento([
        "mensagem" => "Sua solicitação foi enviada com sucesso. Nossa equipe entrará em contato."
    ], 201);

} catch (PDOException $e) {
    error_log("Vizan orçamento: " . $e->getMessage());

    resposta_orcamento([
        "erro" => "Não foi possível registrar sua solicitação agora. Tente novamente mais tarde."
    ], 500);
}
