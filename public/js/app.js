// Mobile sidebar toggle
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (toggle && sidebar && overlay) {
        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }
        toggle.addEventListener('click', function () {
            sidebar.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
        });
        overlay.addEventListener('click', closeSidebar);
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) closeSidebar();
        });
    }
});

// Prevenir acceso al historial (botón atrás) después de cerrar sesión
window.addEventListener("pageshow", function (event) {
    var historyTraversal = event.persisted ||
        (typeof window.performance != "undefined" &&
            window.performance.navigation.type === 2);
    if (historyTraversal) {
        window.location.reload();
    }
});

/**
 * Inicialización genérica de DataTables
 */
function initDataTable(selector, options = {}) {
    const defaultOptions = {
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        responsive: true
    };
    return $(selector).DataTable(Object.assign(defaultOptions, options));
}
