<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";

exigir_login();

$nomeBruto = (string)($_SESSION["usuario_nome"] ?? "Usuário");
$emailBruto = (string)($_SESSION["usuario_email"] ?? "");

$nome = htmlspecialchars($nomeBruto, ENT_QUOTES, "UTF-8");
$email = htmlspecialchars($emailBruto, ENT_QUOTES, "UTF-8");

$inicial = htmlspecialchars(
    mb_substr($nomeBruto, 0, 1, "UTF-8"),
    ENT_QUOTES,
    "UTF-8"
);

$tipo = "Acesso administrativo";

$csrf = htmlspecialchars(
    (string)($_SESSION["csrf_token"] ?? ""),
    ENT_QUOTES,
    "UTF-8"
);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<meta name="csrf-token" content="<?= $csrf ?>">
<meta name="vizan-user" content="<?= (int)($_SESSION["usuario_id"] ?? 0) ?>">

<title>Painel Administrativo | Vizan Engenharia</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="dashboard.css">

</head>

<body>

<div class="layout">

    <aside class="sidebar" id="sidebar">

        <a class="brand" href="vizane.html">
            <img src="VIZAN.jpg.png" alt="Vizan Engenharia">
        </a>

        <div class="side-caption">
            PAINEL ADMINISTRATIVO
        </div>

        <nav class="menu">

            <button class="nav-link active" data-page="inicio">
                <span>▦</span> Visão geral
            </button>

            <button class="nav-link" data-page="conteudo">
                <span>▧</span> Conteúdo do site
            </button>

            <button class="nav-link" data-page="orcamentos">
                <span>✉</span> Solicitações de orçamento
            </button>

            <button class="nav-link" data-page="usuarios">
                <span>♙</span> Usuários e acessos
            </button>

            <button class="nav-link" data-page="perfil">
                <span>⚙</span> Meu perfil
            </button>

        </nav>

        <div class="sidebar-bottom">

            <a href="vizane.html" class="site-link">
                ↗ Visualizar site
            </a>

            <a class="logout" href="logout.php">
                ⇥ Sair do painel
            </a>

        </div>

    </aside>

    <main class="main">

        <header class="topbar">

            <button
                class="mobile-menu"
                id="mobileMenu"
                aria-label="Abrir menu"
            >
                ☰
            </button>

            <div>

                <span class="eyebrow">
                    VIZAN ENGENHARIA E CONSTRUTORA
                </span>

                <h1 id="pageTitle">
                    Visão geral
                </h1>

            </div>

            <div class="admin-mini">

                <div class="avatar">
                    <?= $inicial ?>
                </div>

                <div>

                    <strong><?= $nome ?></strong>

                    <small>
                        <?= htmlspecialchars($tipo, ENT_QUOTES, "UTF-8") ?>
                    </small>

                </div>

            </div>

        </header>

        <!-- VISÃO GERAL -->

        <section class="page active" id="page-inicio">

            <div class="welcome">

                <div>

                    <span class="eyebrow gold">
                        ÁREA RESTRITA
                    </span>

                    <h2>
                        Bem-vindo ao painel, <?= $nome ?>.
                    </h2>

                    <p>
                        Gerencie as informações e acompanhe os contatos recebidos pela Vizan.
                    </p>

                </div>

                <div class="welcome-mark">
                    VIZAN <span>ENGENHARIA</span>
                </div>

            </div>

            <div class="stats">

                <article class="stat-card">
                    <span>Solicitações recebidas</span>
                    <strong id="statTotal">—</strong>
                    <small>Registros de orçamento</small>
                </article>

                <article class="stat-card">
                    <span>Aguardando retorno</span>
                    <strong class="gold-number" id="statPending">—</strong>
                    <small>Precisam de acompanhamento</small>
                </article>

                <article class="stat-card">
                    <span>Imagens cadastradas</span>
                    <strong id="statImages">—</strong>
                    <small>Biblioteca do site</small>
                </article>

                <article class="stat-card">
                    <span>Usuários cadastrados</span>
                    <strong id="statUsers">—</strong>
                    <small>Acessos administrativos</small>
                </article>

            </div>

            <div class="content-grid">

                <article class="panel">

                    <div class="panel-heading">

                        <div>

                            <span class="eyebrow">
                                ACOMPANHAMENTO
                            </span>

                            <h3>Solicitações recentes</h3>

                        </div>

                        <button class="text-action" data-go="orcamentos">
                            Ver todas →
                        </button>

                    </div>

                    <div class="table-wrap">

                        <table>

                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Assunto</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody id="recentQuotes"></tbody>

                        </table>

                    </div>

                </article>

                <article class="panel quick-panel">

                    <span class="eyebrow">ATALHOS</span>

                    <h3>O que deseja fazer?</h3>

                    <button class="quick-action" data-go="conteudo">
                        <span>▧</span>
                        <div>
                            <b>Atualizar o site</b>
                            <small>Descrições e imagens</small>
                        </div>
                        <i>→</i>
                    </button>

                    <button class="quick-action" data-go="orcamentos">
                        <span>✉</span>
                        <div>
                            <b>Ver orçamentos</b>
                            <small>Acompanhar novos contatos</small>
                        </div>
                        <i>→</i>
                    </button>

                    <button class="quick-action" data-go="usuarios">
                        <span>♙</span>
                        <div>
                            <b>Gerenciar acessos</b>
                            <small>Cadastro e remoção de usuários</small>
                        </div>
                        <i>→</i>
                    </button>

                </article>

            </div>

        </section>

        <!-- CONTEÚDO DO SITE -->

        <section class="page" id="page-conteudo">

            <div class="section-intro">

                <div>

                    <span class="eyebrow">
                        SITE INSTITUCIONAL
                    </span>

                    <h2>Conteúdo do site</h2>

                    <p>
                        Edite textos e organize as imagens exibidas nas páginas da Vizan.
                    </p>

                </div>

            </div>

            <div class="content-editor">

                <article class="panel">

                    <h3>Editar descrição</h3>

                    <form id="descriptionForm">

                        <label for="sectionSelect">
                            Seção do site
                        </label>

                        <select id="sectionSelect">
                            <option>Quem Somos</option>
                            <option>Serviços</option>
                            <option>Projetos</option>
                            <option>Onde Estamos</option>
                        </select>

                        <label for="descriptionText">
                            Texto / descrição
                        </label>

                        <textarea
                            id="descriptionText"
                            rows="6"
                            required
                        ></textarea>

                        <button class="primary-btn admin-only" type="submit">
                            Salvar alteração
                        </button>

                        <p class="form-feedback" id="descriptionFeedback"></p>

                    </form>

                </article>

                <article class="panel">

                    <h3>Adicionar imagem</h3>

                    <form id="imageForm">

                        <label class="upload-area" for="imageFile">

                            <span class="upload-icon">＋</span>

                            <b>Escolher imagem</b>

                            <small>
                                JPG, PNG ou WEBP — máximo 5 MB
                            </small>

                            <input
                                type="file"
                                id="imageFile"
                                accept="image/png,image/jpeg,image/webp"
                                required
                            >

                        </label>

                        <label for="imageAlt">
                            Descrição da imagem
                        </label>

                        <input id="imageAlt" required maxlength="180">

                        <button class="primary-btn admin-only" type="submit">
                            Enviar imagem
                        </button>

                        <p class="form-feedback" id="imageFeedback"></p>

                    </form>

                </article>

            </div>

            <article class="panel">

                <h3>Imagens cadastradas</h3>

                <div class="image-list" id="imageList"></div>

            </article>

        </section>

        <!-- ORÇAMENTOS -->

        <section class="page" id="page-orcamentos">

            <div class="section-intro">

                <div>

                    <span class="eyebrow">
                        RELACIONAMENTO COM CLIENTES
                    </span>

                    <h2>Solicitações de orçamento</h2>

                    <p>
                        Pedidos enviados pelo formulário do site.
                    </p>

                </div>

            </div>

            <article class="panel">

                <div class="filters">

                    <input
                        type="search"
                        id="quoteSearch"
                        placeholder="Buscar cliente, e-mail ou assunto"
                    >

                    <select id="quoteFilter">
                        <option value="">Todos os status</option>
                        <option>Pendente</option>
                        <option>Em análise</option>
                        <option>Respondido</option>
                    </select>

                </div>

                <div class="table-wrap">

                    <table>

                        <thead>
                            <tr>
                                <th>Cliente / contato</th>
                                <th>Assunto / mensagem</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>

                        <tbody id="quotesTable"></tbody>

                    </table>

                </div>

            </article>

        </section>

        <!-- USUÁRIOS -->

        <section class="page" id="page-usuarios">

            <div class="section-intro">

                <div>

                    <span class="eyebrow">
                        CONTROLE DE ACESSO
                    </span>

                    <h2>Usuários e acessos</h2>

                    <p>
                        Todos os usuários cadastrados possuem acesso administrativo.
                    </p>

                </div>

            </div>

            <div class="user-layout">

                <article class="panel">

                    <h3>Usuários cadastrados</h3>

                    <div class="table-wrap">

                        <table>

                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>E-mail</th>
                                    <th>Permissão</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>

                            <tbody id="usersTable"></tbody>

                        </table>

                    </div>

                </article>

                <article class="panel">

                    <h3>Cadastrar usuário</h3>

                    <form id="userForm">

                        <label for="newUserName">
                            Usuário
                        </label>

                        <input
                            id="newUserName"
                            required
                            maxlength="120"
                        >

                        <label for="newUserEmail">
                            E-mail
                        </label>

                        <input
                            id="newUserEmail"
                            type="email"
                            required
                            maxlength="150"
                        >

                        <label for="newUserPassword">
                            Senha inicial
                        </label>

                        <input
                            id="newUserPassword"
                            type="password"
                            minlength="8"
                            required
                        >

                        <button class="primary-btn" type="submit">
                            Cadastrar usuário
                        </button>

                        <p class="form-feedback" id="userFeedback"></p>

                    </form>

                </article>

            </div>

        </section>

        <!-- PERFIL -->

        <section class="page" id="page-perfil">

            <div class="section-intro">

                <div>

                    <span class="eyebrow">
                        CONFIGURAÇÕES PESSOAIS
                    </span>

                    <h2>Meu perfil</h2>

                    <p>
                        Atualize o nome de usuário, e-mail e senha da sua conta.
                    </p>

                </div>

            </div>

            <article class="panel profile-panel">

                <div class="profile-heading">

                    <div class="profile-avatar">
                        <?= $inicial ?>
                    </div>

                    <div>
                        <h3><?= $nome ?></h3>
                        <p class="muted">Acesso administrativo</p>
                    </div>

                </div>

                <form id="profileForm" class="profile-form">

                    <label for="profileName">
                        Usuário
                    </label>

                    <input
                        id="profileName"
                        value="<?= $nome ?>"
                        required
                        maxlength="120"
                    >

                    <label for="profileEmail">
                        E-mail
                    </label>

                    <input
                        id="profileEmail"
                        type="email"
                        value="<?= $email ?>"
                        required
                        maxlength="150"
                    >

                    <button class="primary-btn" type="submit">
                        Salvar perfil
                    </button>

                    <p class="form-feedback" id="profileFeedback"></p>

                </form>

                <form id="passwordForm" class="profile-form">

                    <h3>Alterar senha</h3>

                    <label for="currentPassword">
                        Senha atual
                    </label>

                    <input
                        id="currentPassword"
                        type="password"
                        autocomplete="current-password"
                        required
                    >

                    <label for="newPassword">
                        Nova senha (mínimo 8 caracteres)
                    </label>

                    <input
                        id="newPassword"
                        type="password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <label for="confirmPassword">
                        Confirmar nova senha
                    </label>

                    <input
                        id="confirmPassword"
                        type="password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <button class="primary-btn" type="submit">
                        Alterar senha
                    </button>

                    <p class="form-feedback" id="passwordFeedback"></p>

                </form>

            </article>

        </section>

        <footer class="main-footer">
            © 2026 Vizan Engenharia e Construtora. Todos os direitos reservados.
        </footer>

    </main>

</div>

<script src="dashboard-api.js"></script>

</body>
</html>
