<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/auth.php";

$count = (int)$pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && $count === 0) {
    $token = $_POST["csrf"] ?? "";
    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $erro = "Sessão expirada. Atualize a página.";
    } else {
        $nome = trim((string)($_POST["nome"] ?? ""));
        $login = trim((string)($_POST["login"] ?? ""));
        $email = trim((string)($_POST["email"] ?? ""));
        $senha = (string)($_POST["senha"] ?? "");
        if ($nome === "" || $login === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 10) {
            $erro = "Preencha todos os campos. A senha deve ter pelo menos 10 caracteres.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO usuario (nomeusu,loginusu,senhausu,emailusu,tipousu) VALUES (?,?,?,?, 'Administrador')");
                $stmt->execute([$nome, $login, password_hash($senha, PASSWORD_DEFAULT), $email]);
                $sucesso = "Administrador criado. Agora acesse vizanelogin.html e, por segurança, exclua instalar_admin.php do servidor.";
                $count = 1;
            } catch (PDOException $e) {
                $erro = "Não foi possível criar a conta. Confira se o login ou e-mail já está cadastrado.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configurar administrador | Vizan</title>
<style>
*{box-sizing:border-box;font-family:Arial,sans-serif}body{margin:0;background:#07192e;min-height:100vh;display:grid;place-items:center;padding:20px}.card{background:#fff;max-width:460px;width:100%;padding:30px;border-radius:12px;border-top:5px solid #f0a500}h1{color:#0b2545;font-size:22px}p{color:#5d6875;line-height:1.5;font-size:14px}label{display:block;margin:15px 0 6px;font-size:13px;font-weight:bold;color:#24364b}input{width:100%;padding:12px;border:1px solid #d8dee7;border-radius:6px}button{margin-top:20px;width:100%;padding:13px;background:#f0a500;border:0;border-radius:6px;font-weight:bold;color:#07192e;cursor:pointer}.erro{color:#a1261c}.ok{color:#187442}
</style></head><body><main class="card">
<h1>Configuração inicial da Vizan</h1>
<?php if ($count > 0): ?>
<p class="ok">Já existe pelo menos um usuário cadastrado. A criação inicial está bloqueada.</p>
<p><a href="vizanelogin.html">Ir para o login administrativo</a></p>
<?php else: ?>
<p>Crie a primeira conta com nível Administrador. Use uma senha forte e exclusiva.</p>
<?php if ($erro): ?><p class="erro"><?= htmlspecialchars($erro, ENT_QUOTES, "UTF-8") ?></p><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
<label>Nome completo</label><input name="nome" maxlength="120" required>
<label>Login</label><input name="login" maxlength="60" required>
<label>E-mail</label><input type="email" name="email" maxlength="150" required>
<label>Senha (mínimo 10 caracteres)</label><input type="password" name="senha" minlength="10" required>
<button type="submit">Criar administrador</button>
</form>
<?php endif; ?>
</main></body></html>
