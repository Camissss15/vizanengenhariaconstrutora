"use strict";

const pages = ["inicio", "conteudo", "orcamentos", "usuarios", "perfil"];
const titles = {
    inicio: "Visão geral",
    conteudo: "Conteúdo do site",
    orcamentos: "Solicitações de orçamento",
    usuarios: "Usuários e acessos",
    perfil: "Meu perfil"
};
const $ = id => document.getElementById(id);
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";
const isAdmin = document.querySelector('meta[name="vizan-admin"]')?.content === "1";

async function api(acao, options = {}) {
    const headers = new Headers(options.headers || {});
    if (options.method && options.method.toUpperCase() !== "GET") {
        headers.set("X-CSRF-Token", csrfToken);
    }
    const response = await fetch("api.php?acao=" + encodeURIComponent(acao), {
        ...options,
        headers
    });
    let data;
    try { data = await response.json(); }
    catch { throw new Error("O servidor retornou uma resposta inválida."); }
    if (!response.ok) throw new Error(data.erro || "Ocorreu um erro.");
    return data;
}

function jsonOptions(data) {
    return {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    };
}

function esc(value) {
    return String(value ?? "").replace(/[&<>"']/g, char => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    }[char]));
}

function page(pageName) {
    if (pageName === "usuarios" && !isAdmin) return;
    pages.forEach(name => {
        const section = $("page-" + name);
        if (section) section.classList.toggle("active", name === pageName);
    });
    document.querySelectorAll(".nav-link").forEach(button => {
        button.classList.toggle("active", button.dataset.page === pageName);
    });
    if ($("pageTitle")) $("pageTitle").textContent = titles[pageName] || "Painel";
    $("sidebar")?.classList.remove("open");
}

document.querySelectorAll(".nav-link").forEach(button => {
    button.addEventListener("click", () => page(button.dataset.page));
});
document.querySelectorAll("[data-go]").forEach(button => {
    button.addEventListener("click", () => page(button.dataset.go));
});
$("mobileMenu")?.addEventListener("click", () => $("sidebar")?.classList.toggle("open"));

function statusBadge(value) {
    const cls = value === "Respondido" ? "respondido" : value === "Em análise" ? "analise" : "";
    return `<span class="status ${cls}">${esc(value)}</span>`;
}

function formatDate(value) {
    if (!value) return "—";
    const parsed = new Date(String(value).replace(" ", "T"));
    return Number.isNaN(parsed.getTime()) ? esc(value) :
        parsed.toLocaleString("pt-BR", { dateStyle: "short", timeStyle: "short" });
}

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
    `).join("") || "<tr><td colspan='4'>Nenhuma solicitação recebida.</td></tr>";
}

async function carregarOrcamentos() {
    const all = await api("orcamentos");
    const termo = ($("quoteSearch")?.value || "").toLocaleLowerCase("pt-BR");
    const filtro = $("quoteFilter")?.value || "";
    const filtered = all.filter(item =>
        (!filtro || item.status === filtro) &&
        [item.nome, item.email, item.assunto, item.mensagem]
            .join(" ").toLocaleLowerCase("pt-BR").includes(termo)
    );

    $("quotesTable").innerHTML = filtered.map(item => {
        const controls = isAdmin ? `
            <select data-status="${item.id}" aria-label="Alterar status do orçamento">
                <option ${item.status === "Pendente" ? "selected" : ""}>Pendente</option>
                <option ${item.status === "Em análise" ? "selected" : ""}>Em análise</option>
                <option ${item.status === "Respondido" ? "selected" : ""}>Respondido</option>
            </select>
            <button class="small-btn delete-btn" data-quote-del="${item.id}">Excluir</button>
        ` : `<span class="muted">Somente visualização</span>`;

        return `
            <tr>
                <td><b>${esc(item.nome)}</b><br>
                    <a href="mailto:${encodeURIComponent(item.email)}">${esc(item.email)}</a><br>
                    ${esc(item.telefone || "Telefone não informado")}
                </td>
                <td><b>${esc(item.assunto)}</b>
                    <details class="quote-message"><summary>Ver mensagem</summary><p>${esc(item.mensagem)}</p></details>
                </td>
                <td>${formatDate(item.data_envio)}</td>
                <td>${statusBadge(item.status)}</td>
                <td class="quote-actions">${controls}</td>
            </tr>
        `;
    }).join("") || "<tr><td colspan='5'>Nenhuma solicitação encontrada.</td></tr>";

    if (isAdmin) {
        document.querySelectorAll("[data-status]").forEach(select => {
            select.addEventListener("change", async () => {
                try {
                    await api("orcamento_status", jsonOptions({
                        id: Number(select.dataset.status),
                        status: select.value
                    }));
                    await carregarOrcamentos();
                    await carregarResumo();
                } catch (error) { alert(error.message); }
            });
        });

        document.querySelectorAll("[data-quote-del]").forEach(button => {
            button.addEventListener("click", async () => {
                if (!confirm("Tem certeza de que deseja excluir esta solicitação?")) return;
                try {
                    await api("orcamento_excluir", jsonOptions({ id: Number(button.dataset.quoteDel) }));
                    await carregarOrcamentos();
                    await carregarResumo();
                } catch (error) { alert(error.message); }
            });
        });
    }
}

async function carregarConteudo() {
    const data = await api("conteudo");
    const select = $("sectionSelect");
    const textarea = $("descriptionText");
    const options = ["Quem Somos", "Serviços", "Projetos", "Onde Estamos"];
    select.innerHTML = "";
    options.forEach(value => select.add(new Option(value, value)));

    const preencher = () => {
        const current = data.find(item => item.secao === select.value);
        textarea.value = current?.descricao || "";
    };
    preencher();
    select.addEventListener("change", preencher);

    if (!isAdmin) {
        textarea.readOnly = true;
        select.disabled = true;
        $("imageFile").disabled = true;
        $("imageAlt").disabled = true;
        document.querySelectorAll(".admin-only").forEach(button => button.hidden = true);
        $("descriptionFeedback").textContent = "Seu perfil tem permissão de consulta. Apenas administradores podem editar.";
        $("imageFeedback").textContent = "Apenas administradores podem cadastrar ou excluir imagens.";
    }

    const images = await api("imagens");
    $("imageList").innerHTML = images.map(image => `
        <div class="image-thumb">
            <img src="${esc(image.caminho)}" alt="${esc(image.descricao)}" loading="lazy">
            <p>${esc(image.descricao)}<br>
            ${isAdmin ? `<button class="small-btn delete-btn" data-imgdel="${image.id}">Excluir</button>` : ""}</p>
        </div>
    `).join("") || "<p class='muted'>Nenhuma imagem cadastrada.</p>";

    document.querySelectorAll("[data-imgdel]").forEach(button => {
        button.addEventListener("click", async () => {
            if (!confirm("Excluir esta imagem?")) return;
            try {
                await api("imagem_excluir", jsonOptions({ id: Number(button.dataset.imgdel) }));
                await carregarConteudo();
                await carregarResumo();
            } catch (error) { alert(error.message); }
        });
    });
}

$("descriptionForm")?.addEventListener("submit", async event => {
    event.preventDefault();
    if (!isAdmin) return;
    try {
        const data = await api("conteudo", jsonOptions({
            secao: $("sectionSelect").value,
            descricao: $("descriptionText").value
        }));
        $("descriptionFeedback").textContent = data.mensagem;
    } catch (error) {
        $("descriptionFeedback").textContent = error.message;
    }
});

$("imageForm")?.addEventListener("submit", async event => {
    event.preventDefault();
    if (!isAdmin) return;
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
        await carregarConteudo();
        await carregarResumo();
    } catch (error) {
        $("imageFeedback").textContent = error.message;
    }
});

async function carregarUsuarios() {
    if (!isAdmin || !$("usersTable")) return;
    const users = await api("usuarios");
    $("usersTable").innerHTML = users.map(user => `
        <tr>
            <td>${esc(user.nomeusu)}</td>
            <td>${esc(user.loginusu)}</td>
            <td>${esc(user.tipousu)}</td>
            <td><button class="small-btn delete-btn" data-userdel="${user.Idu}">Excluir</button></td>
        </tr>
    `).join("") || "<tr><td colspan='4'>Nenhum usuário cadastrado.</td></tr>";

    document.querySelectorAll("[data-userdel]").forEach(button => {
        button.addEventListener("click", async () => {
            if (!confirm("Deseja excluir este usuário?")) return;
            try {
                await api("usuario_excluir", jsonOptions({ id: Number(button.dataset.userdel) }));
                await carregarUsuarios();
                await carregarResumo();
            } catch (error) { alert(error.message); }
        });
    });
}

$("userForm")?.addEventListener("submit", async event => {
    event.preventDefault();
    try {
        const data = await api("usuario_criar", jsonOptions({
            nome: $("newUserName").value,
            login: $("newUserLogin").value,
            email: $("newUserEmail").value,
            senha: $("newUserPassword").value,
            tipo: $("newUserRole").value
        }));
        $("userFeedback").textContent = data.mensagem;
        event.target.reset();
        await carregarUsuarios();
        await carregarResumo();
    } catch (error) {
        $("userFeedback").textContent = error.message;
    }
});

$("profileForm")?.addEventListener("submit", async event => {
    event.preventDefault();
    try {
        const data = await api("perfil", jsonOptions({
            nome: $("profileName").value,
            email: $("profileEmail").value
        }));
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
        $("passwordFeedback").textContent = "A confirmação da nova senha não corresponde.";
        return;
    }
    try {
        const data = await api("senha", jsonOptions({
            atual: $("currentPassword").value,
            nova
        }));
        $("passwordFeedback").textContent = data.mensagem;
        event.target.reset();
    } catch (error) {
        $("passwordFeedback").textContent = error.message;
    }
});

$("quoteSearch")?.addEventListener("input", () => carregarOrcamentos().catch(e => alert(e.message)));
$("quoteFilter")?.addEventListener("change", () => carregarOrcamentos().catch(e => alert(e.message)));

(async function iniciarPainel() {
    try {
        await carregarResumo();
        await carregarOrcamentos();
        await carregarConteudo();
        if (isAdmin) await carregarUsuarios();
    } catch (error) {
        alert(error.message);
    }
})();
