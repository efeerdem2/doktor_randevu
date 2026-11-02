(() => {
    const temaAnahtari = 'doktor-randevu-temasi';
    const gecerliTemalar = ['light', 'dark', 'system'];
    const kayitliTema = localStorage.getItem(temaAnahtari);
    const ilkTema = gecerliTemalar.includes(kayitliTema) ? kayitliTema : 'system';

    function temaUygula(tema) {
        document.documentElement.dataset.theme = tema;
        localStorage.setItem(temaAnahtari, tema);

        document.querySelectorAll('[data-theme-option]').forEach((dugme) => {
            const secili = dugme.dataset.themeOption === tema;
            dugme.classList.toggle('active', secili);
            dugme.setAttribute('aria-pressed', String(secili));
        });
    }

    document.documentElement.dataset.theme = ilkTema;

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-theme-option]').forEach((dugme) => {
            dugme.addEventListener('click', () => temaUygula(dugme.dataset.themeOption));
        });
        temaUygula(ilkTema);
    });
})();
