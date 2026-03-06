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
