<?php
/**
 * HospitAll - Root entry point.
 * Redirects all requests to public/ so that public/index.php is the sole handler.
 * Configure Apache DocumentRoot to project root, or use public/ as DocumentRoot.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$query = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_QUERY);
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if ($base === '' || $base === '.') {
    $base = '/';
}
$dest = $base . '/public' . ($path ?: '/');
if ($query) {
    $dest .= '?' . $query;
}
header('Location: ' . $dest, true, 302);
exit;
