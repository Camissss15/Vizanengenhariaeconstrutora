
document.addEventListener("DOMContentLoaded", () => {

    // Seleciona as seções do site e o rodapé
    const secoes = document.querySelectorAll(
        "main > section, footer.rodape"
    );

    // Verifica se o navegador suporta a animação
    if (!("IntersectionObserver" in window)) {
        secoes.forEach(secao => {
            secao.classList.add("secao-visivel");
        });
        return;
    }

    // Configura o observador das seções
    const observador = new IntersectionObserver(
        (entradas, observador) => {

            entradas.forEach(entrada => {

                if (entrada.isIntersecting) {

                    // Mostra a seção com animação
                    entrada.target.classList.add("secao-visivel");

                    // Evita repetir a animação
                    observador.unobserve(entrada.target);
                }

            });

        },
        {
            threshold: 0.12
        }
    );

    // Prepara e observa cada seção
    secoes.forEach(secao => {
        secao.classList.add("animar-secao");
        observador.observe(secao);
    });

});
