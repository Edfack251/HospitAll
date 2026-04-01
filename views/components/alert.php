<?php
/**
 * Componente de alerta reutilizable
 * Uso: include con variables $alert_type y $alert_message
 * 
 * $alert_type: 'success' | 'error' | 'warning' | 'info'
 * $alert_message: string con el mensaje a mostrar
 * $alert_dismissible: bool (opcional, default true)
 */

if (!isset($alert_type) || !isset($alert_message)) return;
if (empty($alert_message)) return;

$alert_dismissible = $alert_dismissible ?? true;

$styles = [
    'success' => [
        'container' => 'bg-green-50 border border-green-400 text-green-800',
        'icon_color' => 'text-green-500',
        'icon' => '✓'
    ],
    'error' => [
        'container' => 'bg-red-50 border border-red-400 text-red-800',
        'icon_color' => 'text-red-500',
        'icon' => '✗'
    ],
    'warning' => [
        'container' => 'bg-yellow-50 border border-yellow-400 text-yellow-800',
        'icon_color' => 'text-yellow-500',
        'icon' => '!'
    ],
    'info' => [
        'container' => 'bg-blue-50 border border-blue-400 text-blue-800',
        'icon_color' => 'text-blue-500',
        'icon' => 'i'
    ],
];

$style = $styles[$alert_type] ?? $styles['info'];
?>

<div class="<?php echo $style['container']; ?> px-4 py-3 rounded-lg mb-4 flex items-start gap-3"
     role="alert">
    <span class="<?php echo $style['icon_color']; ?> font-bold text-lg">
        <?php echo $style['icon']; ?>
    </span>
    <div class="flex-1">
        <p class="text-sm font-medium">
            <?php echo htmlspecialchars($alert_message); ?>
        </p>
    </div>
    <?php if ($alert_dismissible): ?>
    <button onclick="this.parentElement.remove()"
            class="ml-auto text-current opacity-50 hover:opacity-100 text-lg font-bold">
        ×
    </button>
    <?php endif; ?>
</div>
