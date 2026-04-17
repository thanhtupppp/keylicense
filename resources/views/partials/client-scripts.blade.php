<script>
(() => {
    const themes = {
        success: ['rgba(34,197,94,.22)', 'rgba(34,197,94,.12)', '#bbf7d0'],
        danger: ['rgba(239,68,68,.24)', 'rgba(239,68,68,.12)', '#fecaca'],
        info: ['rgba(59,130,246,.2)', 'rgba(59,130,246,.12)', '#dbeafe'],
        warning: ['rgba(245,158,11,.22)', 'rgba(245,158,11,.12)', '#fde68a'],
    };

    const setLoading = (button, loadingText = 'Đang xử lý...') => {
        if (!button) return () => {};
        const originalText = button.dataset.originalText || button.textContent;
        button.dataset.originalText = originalText;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.textContent = loadingText;
        return () => {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            button.textContent = originalText;
        };
    };

    const setToast = (element, type, message) => {
        if (!element) return;
        const [border, bg, color] = themes[type] || themes.info;
        const toast = element.matches?.('[data-role="toast"]') ? element : element.querySelector?.('[data-role="toast"]') || element;
        toast.style.display = 'block';
        toast.textContent = message;
        toast.style.borderColor = border;
        toast.style.background = bg;
        toast.style.color = color;
    };

    const setPanelState = (panel, state, message = null) => {
        if (!panel) return;
        const result = panel.querySelector?.('[data-role="result"]');
        const toast = panel.querySelector?.('[data-role="toast"]');
        if (result && message !== null) result.textContent = message;
        if (toast) setToast(toast, state === 'error' ? 'danger' : state, message || (state === 'loading' ? 'Đang xử lý...' : ''));
        panel.dataset.state = state;
    };

    window.ClientUI = { setLoading, setToast, setPanelState };
})();
</script>
