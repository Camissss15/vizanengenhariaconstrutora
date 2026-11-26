"use strict";

const pages = ["inicio", "conteudo", "orcamentos", "usuarios", "perfil"];

const titles = {
    inicio: "Visão geral",
    conteudo: "Conteúdo do site",
    orcamentos: "Solicitações de orçamento",
    usuarios: "Usuários e acessos",
    perfil: "Meu perfil"
};

const SECOES = [
    "Quem Somos",
    "Serviços",
    "Projetos",
    "Onde Estamos"
];

const $ = id => document.getElementById(id);

const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

const usuarioAtual = Number(
    document.querySelector('meta[name="vizan-user"]')?.content || 0
);

let conteudoCache = [];

async function api(acao, options = {}) {
    const headers = new Headers(options.headers || {});

    if (options.method && options.method.toUpperCase() !== "GET") {
        headers.set("X-CSRF-Token", csrfToken);
    }

    const response = await fetch(
        "api.php?acao=" + encodeURIComponent(acao),
        {
            ...options,
            headers,
            credentials: "same-origin"
        }
    );

    if (response.status === 401) {
        window.location.href = "vizanelogin.html";
        throw new Error("Sessão expirada. Faça login novamente.");
    }

    let data;

    try {
        data = await response.json();
    } catch {
        throw new Error("O servidor retornou uma resposta inválida.");
    }

    if (!response.ok) {
        throw new Error(data.erro || "Ocorreu um erro.");
    }

    return data;
}

function jsonOptions(data) {
    return {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    };
}

function esc(value) {
    return String(value ?? "").replace(/[&<>"']/g, char => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
    }[char]));
}

function page(pageName) {
    pages.forEach(name => {
        const section = $("page-" + name);

        if (section) {
            section.classList.toggle("active", name === pageName);
        }
    });

    document.querySelectorAll(".nav-link").forEach(button => {
        button.classList.toggle(
            "active",
            button.dataset.page === pageName
        );
    });

    if ($("pageTitle")) {
        $("pageTitle").textContent = titles[pageName] || "Painel";
    }

    $("sidebar")?.classList.remove("open");

    window.scrollTo({ top: 0 });
}

document.querySelectorAll(".nav-link").forEach(button => {
    button.addEventListener("click", () => {
        page(button.dataset.page);
    });
});

document.querySelectorAll("[data-go]").forEach(button => {
    button.addEventListener("click", () => {
        page(button.dataset.go);
    });
});

$("mobileMenu")?.addEventListener("click", () => {
    $("sidebar")?.classList.toggle("open");
});

function statusBadge(value) {
    const cls =
        value === "Respondido"
            ? "respondido"
            : value === "Em análise"
                ? "analise"
                : "";

    return `<span class="status ${cls}">${esc(value)}</span>`;
}

function formatDate(value) {
    if (!value) return "—";

    const parsed = new Date(String(value).replace(" ", "T"));

    return Number.isNaN(parsed.getTime())
        ? esc(value)
        : parsed.toLocaleString("pt-BR", {
            dateStyle: "short",
            timeStyle: "short"
        });
}

/* VISÃO GERAL */

async function carregarResumo() {
    const data = await api("resumo");

    $("statTotal").textContent = data.total;
    $("statPending").textContent = data.pendentes;
    $("statImages").textContent = data.imagens;
    $("statUsers").textContent = data.usuarios;

    $("recentQuotes").innerHTML = data.recentes.map(item => `
        <tr>
            <td>${esc(item.nome)}</td>
            <td>${esc(item.assunto)}</td>
            <td>${formatDate(item.data_envio)}</td>
            <td>${statusBadge(item.status)}</td>
        </tr>
    `).join("") ||
    "<tr><td colspan='4'>Nenhuma solicitação recebida.</td></tr>";
}

/* ORÇAMENTOS */

async function carregarOrcamentos() {
    const all = await api("orcamentos");

    const termo = (
        $("quoteSearch")?.value || ""
    ).toLocaleLowerCase("pt-BR");

    const filtro = $("quoteFilter")?.value || "";

    const filtered = all.filter(item =>
        (!filtro || item.status === filtro) &&
        [
            item.nome,
            item.email,
            item.assunto,
            item.mensagem
        ]
        .join(" ")
        .toLocaleLowerCase("pt-BR")
        .includes(termo)
    );

    $("quotesTable").innerHTML = filtered.map(item => `
        <tr>
            <td>
                <b>${esc(item.nome)}</b><br>
                <a href="mailto:${esc(item.email)}">
                    ${esc(item.email)}
                </a><br>
                ${esc(item.telefone || "Telefone não informado")}
            </td>

            <td>
                <b>${esc(item.assunto)}</b>

                <details class="quote-message">
                    <summary>Ver mensagem</summary>
                    <p>${esc(item.mensagem)}</p>
                </details>
            </td>

            <td>${formatDate(item.data_envio)}</td>

            <td>${statusBadge(item.status)}</td>

            <td class="quote-actions">
                <select
                    data-status="${item.id}"
                    aria-label="Alterar status do orçamento"
                >
                    <option ${item.status === "Pendente" ? "selected" : ""}>
                        Pendente
                    </option>

                    <option ${item.status === "Em análise" ? "selected" : ""}>
                        Em análise
                    </option>

                    <option ${item.status === "Respondido" ? "selected" : ""}>
                        Respondido
                    </option>
                </select>

                <button
                    class="small-btn delete-btn"
                    data-quote-del="${item.id}"
                >
                    Excluir
                </button>
            </td>
        </tr>
    `).join("") ||
    "<tr><td colspan='5'>Nenhuma solicitação encontrada.</td></tr>";

    document.querySelectorAll("[data-status]").forEach(select => {
        select.addEventListener("change", async () => {
            try {
                await api(
                    "orcamento_status",
                    jsonOptions({
                        id: Number(select.dataset.status),
                        status: select.value
                    })
                );

                await carregarOrcamentos();
                await carregarResumo();

            } catch (error) {
                alert(error.message);
            }
        });
    });

    document.querySelectorAll("[data-quote-del]").forEach(button => {
        button.addEventListener("click", async () => {
            if (!confirm(
                "Tem certeza de que deseja excluir esta solicitação?"
            )) return;

            try {
                await api(
                    "orcamento_excluir",
                    jsonOptions({
                        id: Number(button.dataset.quoteDel)
                    })
                );

                await carregarOrcamentos();
                await carregarResumo();

            } catch (error) {
                alert(error.message);
            }
        });
    });
}

/* CONTEÚDO DO SITE */

function preencherDescricao() {
    const atual = conteudoCache.find(
        item => item.secao === $("sectionSelect").value
    );

    $("descriptionText").value = atual?.descricao || "";
}

async function iniciarConteudo() {
    conteudoCache = await api("conteudo");

    const select = $("sectionSelect");
    select.innerHTML = "";

    SECOES.forEach(value => {
        select.add(new Option(value, value));
    });

    select.addEventListener("change", preencherDescricao);

    preencherDescricao();
}

async function carregarImagens() {
    const images = await api("imagens");

    $("imageList").innerHTML = images.map(image => `
        <div class="image-thumb">
            <img
                src="${esc(image.caminho)}"
                alt="${esc(image.descricao)}"
                loading="lazy"
            >

            <p>
                ${esc(image.descricao)}<br>

                <button
                    class="small-btn delete-btn"
                    data-imgdel="${image.id}"
                >
                    Excluir
                </button>
            </p>
        </div>
    `).join("") ||
    "<p class='muted'>Nenhuma imagem cadastrada.</p>";

    document.querySelectorAll("[data-imgdel]").forEach(button => {
        button.addEventListener("click", async () => {
            if (!confirm("Excluir esta imagem?")) return;

            try {
                await api(
                    "imagem_excluir",
                    jsonOptions({
                        id: Number(button.dataset.imgdel)
                    })
                );

                await carregarImagens();
                await carregarResumo();

            } catch (error) {
                alert(error.message);
            }
        });
    });
}

$("descriptionForm")?.addEventListener("submit", async event => {
    event.preventDefault();

    const secao = $("sectionSelect").value;
    const descricao = $("descriptionText").value;

    try {
        const data = await api(
            "conteudo",
            jsonOptions({ secao, descricao })
        );

        const existente = conteudoCache.find(
            item => item.secao === secao
        );

        if (existente) {
            existente.descricao = descricao.trim();
        } else {
            conteudoCache.push({
                secao,
                descricao: descricao.trim()
            });
        }

        $("descriptionFeedback").textContent = data.mensagem;

    } catch (error) {
        $("descriptionFeedback").textContent = error.message;
    }
});

$("imageForm")?.addEventListener("submit", async event => {
    event.preventDefault();

    const file = $("imageFile").files[0];

    if (!file) {
        $("imageFeedback").textContent = "Selecione uma imagem.";
        return;
    }

    const formData = new FormData();

    formData.append("imagem", file);
    formData.append("descricao", $("imageAlt").value);

    try {
        const data = await api("imagem_upload", {
            method: "POST",
            body: formData
        });

        $("imageFeedback").textContent = data.mensagem;

        event.target.reset();

        await carregarImagens();
        await carregarResumo();

    } catch (error) {
        $("imageFeedback").textContent = error.message;
    }
});

/* USUÁRIOS */

async function carregarUsuarios() {
    const users = await api("usuarios");

    $("usersTable").innerHTML = users.map(user => `
        <tr>
            <td>${esc(user.usuario)}</td>
            <td>${esc(user.emailusu)}</td>
            <td>Administrador</td>

            <td>
                ${Number(user.Idu) === usuarioAtual
                    ? "<span class='muted'>Sua conta</span>"
                    : `<button
                        class="small-btn delete-btn"
                        data-userdel="${user.Idu}"
                    >Excluir</button>`
                }
            </td>
        </tr>
    `).join("") ||
    "<tr><td colspan='4'>Nenhum usuário cadastrado.</td></tr>";

    document.querySelectorAll("[data-userdel]").forEach(button => {
        button.addEventListener("click", async () => {
            if (!confirm("Deseja excluir este usuário?")) return;

            try {
                await api(
                    "usuario_excluir",
                    jsonOptions({
                        id: Number(button.dataset.userdel)
                    })
                );

                await carregarUsuarios();
                await carregarResumo();

            } catch (error) {
                alert(error.message);
            }
        });
    });
}

$("userForm")?.addEventListener("submit", async event => {
    event.preventDefault();

    try {
        const data = await api(
            "usuario_criar",
            jsonOptions({
                nome: $("newUserName").value,
                email: $("newUserEmail").value,
                senha: $("newUserPassword").value
            })
        );

        $("userFeedback").textContent = data.mensagem;

        event.target.reset();

        await carregarUsuarios();
        await carregarResumo();

    } catch (error) {
        $("userFeedback").textContent = error.message;
    }
});

/* PERFIL */

$("profileForm")?.addEventListener("submit", async event => {
    event.preventDefault();

    try {
        const data = await api(
            "perfil",
            jsonOptions({
                nome: $("profileName").value,
                email: $("profileEmail").value
            })
        );

        $("profileFeedback").textContent = data.mensagem;

        setTimeout(() => location.reload(), 700);

    } catch (error) {
        $("profileFeedback").textContent = error.message;
    }
});

$("passwordForm")?.addEventListener("submit", async event => {
    event.preventDefault();

    const nova = $("newPassword").value;

    if (nova !== $("confirmPassword").value) {
        $("passwordFeedback").textContent =
            "A confirmação da nova senha não corresponde.";

        return;
    }

    try {
        const data = await api(
            "senha",
            jsonOptions({
                atual: $("currentPassword").value,
                nova
            })
        );

        $("passwordFeedback").textContent = data.mensagem;

        event.target.reset();

    } catch (error) {
        $("passwordFeedback").textContent = error.message;
    }
});

$("quoteSearch")?.addEventListener(
    "input",
    () => carregarOrcamentos().catch(e => alert(e.message))
);

$("quoteFilter")?.addEventListener(
    "change",
    () => carregarOrcamentos().catch(e => alert(e.message))
);

/* INICIALIZAÇÃO */

(async function iniciarPainel() {
    const etapas = [
        carregarResumo,
        carregarOrcamentos,
        iniciarConteudo,
        carregarImagens,
        carregarUsuarios
    ];

    for (const etapa of etapas) {
        try {
            await etapa();
        } catch (error) {
            console.error(error);
        }
    }
})();
