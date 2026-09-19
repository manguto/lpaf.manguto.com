/**
 * LPAF - Scripts de Interface e Acessibilidade (app.js)
 * Gerencia a navegação responsiva, centralização de itens ativos e comportamento móvel.
 */
document.addEventListener('DOMContentLoaded', () => {
    initSubnavAccessibility();
});

function initSubnavAccessibility() {
    const subnavList = document.getElementById('subnavList');
    const subnavWrapper = document.getElementById('subnavScrollWrapper');
    const toggleBtn = document.getElementById('subnavMobileToggle');
    const dropdown = document.getElementById('subnavMobileDropdown');
    const closeBtn = document.getElementById('subnavMobileClose');

    // 1. Auto-scroll do item ativo para o centro da visualização no celular
    const activeItem = document.querySelector('.subnav-item.active');
    if (activeItem && subnavList) {
        // Pequeno atraso para garantir cálculo correto de layout no mobile
        setTimeout(() => {
            activeItem.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
            updateScrollMask();
        }, 60);
    }

    // 2. Indicador dinâmico de transbordo (Fade Gradients) nas extremidades
    function updateScrollMask() {
        if (!subnavList || !subnavWrapper) return;
        const maxScroll = subnavList.scrollWidth - subnavList.clientWidth;
        
        // Se não houver transbordo, remove qualquer máscara
        if (maxScroll <= 4) {
            subnavWrapper.style.maskImage = 'none';
            subnavWrapper.style.webkitMaskImage = 'none';
            return;
        }

        const sl = subnavList.scrollLeft;
        const atStart = sl <= 6;
        const atEnd = sl >= (maxScroll - 6);

        let mask = '';
        if (atStart && !atEnd) {
            mask = 'linear-gradient(to right, black calc(100% - 28px), transparent 100%)';
        } else if (!atStart && atEnd) {
            mask = 'linear-gradient(to left, black calc(100% - 28px), transparent 100%)';
        } else {
            mask = 'linear-gradient(to right, transparent 0%, black 28px, black calc(100% - 28px), transparent 100%)';
        }

        subnavWrapper.style.maskImage = mask;
        subnavWrapper.style.webkitMaskImage = mask;
    }

    if (subnavList) {
        subnavList.addEventListener('scroll', updateScrollMask, { passive: true });
        window.addEventListener('resize', updateScrollMask, { passive: true });
        updateScrollMask();
    }

    // 3. Controle da Gaveta / Dropdown de Opções no Mobile
    if (toggleBtn && dropdown) {
        function toggleMenu(forceOpen) {
            const isCurrentlyHidden = dropdown.hasAttribute('hidden');
            const shouldOpen = forceOpen !== undefined ? forceOpen : isCurrentlyHidden;

            if (shouldOpen) {
                dropdown.removeAttribute('hidden');
                toggleBtn.setAttribute('aria-expanded', 'true');
                const focusTarget = dropdown.querySelector('.subnav-mobile-link.active') || dropdown.querySelector('.subnav-mobile-link');
                if (focusTarget) {
                    focusTarget.focus();
                }
            } else {
                dropdown.setAttribute('hidden', '');
                toggleBtn.setAttribute('aria-expanded', 'false');
                toggleBtn.focus();
            }
        }

        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleMenu();
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleMenu(false);
            });
        }

        // Fecha ao pressionar ESC para total acessibilidade de teclado
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !dropdown.hasAttribute('hidden')) {
                toggleMenu(false);
            }
        });

        // Fecha ao clicar fora
        document.addEventListener('click', (e) => {
            if (!dropdown.hasAttribute('hidden') && !dropdown.contains(e.target) && !toggleBtn.contains(e.target)) {
                toggleMenu(false);
            }
        });
    }
}
