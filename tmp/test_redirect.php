<?php
$_SERVER['REQUEST_URI'] = '/HospitAll%20V1/';
$_SERVER['SCRIPT_NAME'] = '/HospitAll V1/index.php';

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = urldecode(parse_url($uri, PHP_URL_PATH));
$query = parse_url($uri, PHP_URL_QUERY);

$script = $_SERVER['SCRIPT_NAME'] ?? '';
$base = str_replace('\\', '/', rtrim(dirname($script), '/\\'));

if ($base === '' || $base === '.') {
    $base = '/';
}

$relPath = $path;
if ($base !== '/' && stripos($path, $base) === 0) {
    $relPath = substr($path, strlen($base));
}

$dest = rtrim($base, '/') . '/public/' . ltrim($relPath, '/');

if ($query) {
    $dest .= '?' . $query;
}

echo "Path: $path\n";
echo "Base: $base\n";
echo "RelPath: $relPath\n";
echo "Dest: $dest\n";
