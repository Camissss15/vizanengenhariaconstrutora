document.addEventListener("DOMContentLoaded", () => {
    const secoes = document.querySelectorAll("main > section, footer.rodape");

    if ("IntersectionObserver" in window) {
        const observador = new IntersectionObserver((entradas, obs) => {
            entradas.forEach(entrada => {
                if (entrada.isIntersecting) {
                    entrada.target.classList.add("secao-visivel");
                    obs.unobserve(entrada.target);
                }
            });
        }, { threshold: 0.12 });

        secoes.forEach(secao => {
            secao.classList.add("animar-secao");
            observador.observe(secao);
        });
    } else {
        secoes.forEach(secao => secao.classList.add("secao-visivel"));
    }

    const form = document.getElementById("formOrcamento");

    if (form) {
        form.addEventListener("submit", async event => {
            event.preventDefault();

            const feedback = document.getElementById("retornoOrcamento");
            const botao = form.querySelector('button[type="submit"]');

            feedback.textContent = "";
            feedback.className = "";

            botao.disabled = true;
            botao.textContent = "Enviando...";

            try {
                const response = await fetch(form.action, {
                    method: "POST",
                    body: new FormData(form),
                    headers: { "Accept": "application/json" }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.erro || "Não foi possível enviar sua solicitação.");
                }

                feedback.textContent = data.mensagem;
                feedback.classList.add("sucesso");
                form.reset();

            } catch (error) {
                feedback.textContent = error.message || "Erro de comunicação. Tente novamente.";
                feedback.classList.add("erro");

            } finally {
                botao.disabled = false;
                botao.textContent = "Enviar Solicitação";
            }
        });
    }

    fetch("api.php?acao=conteudo_publico")
        .then(r => r.ok ? r.json() : [])
        .then(itens => {
            if (!Array.isArray(itens)) return;

            itens.forEach(item => {
                if (!item.descricao) return;

                document.querySelectorAll("[data-secao]").forEach(alvo => {
                    if (alvo.dataset.secao === item.secao) {
                        alvo.textContent = item.descricao;
                    }
                });
            });
        })
        .catch(() => {});

    fetch("api.php?acao=imagens_publicas")
        .then(r => r.ok ? r.json() : [])
        .then(imagens => {
            const grade = document.querySelector("#projetos .projetos-grid");

            if (!grade || !Array.isArray(imagens)) return;

            imagens.forEach(item => {
                const card = document.createElement("article");
                card.className = "projeto-card projeto-imagem-dinamica";

                const img = document.createElement("img");
                img.src = item.caminho;
                img.alt = item.descricao || "Imagem de projeto da Vizan";
                img.loading = "lazy";
                img.style.width = "100%";
                img.style.height = "210px";
                img.style.objectFit = "cover";

                const info = document.createElement("div");
                info.className = "projeto-info";

                const titulo = document.createElement("h3");
                titulo.textContent = item.descricao || "Projeto Vizan";

                info.appendChild(titulo);
                card.append(img, info);
                grade.appendChild(card);
            });
        })
        .catch(() => {});

    document.querySelectorAll(".menu-desktop a").forEach(link => {
        link.addEventListener("click", () => {
            const checkbox = document.getElementById("menu");

            if (checkbox) {
                checkbox.checked = false;
            }
        });
    });
});
