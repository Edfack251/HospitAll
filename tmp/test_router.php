<?php
require __DIR__ . '/../vendor/autoload.php';

class MockRouter
{
    private $routes = [];
    public function add($p, $a)
    {
        $this->routes[$p] = $a;
    }
    public function addView($p, $v)
    {
        $this->routes[$p] = $v;
    }

    public function getUrl($requestUri, $scriptName)
    {
        $url = parse_url($requestUri, PHP_URL_PATH);
        $sn = dirname($scriptName);
        if ($sn !== '/' && $sn !== '\\') {
            $url = str_replace($sn, '', $url);
        }
        $url = str_replace('/public', '', $url);
        $url = str_replace('.php', '', $url);
        if (empty($url))
            $url = '/';
        return $url;
    }
}

$router = new MockRouter();
$router->addView('/', 'login.php');

$testCases = [
    ['uri' => '/HospitAll V1/public/index.php', 'script' => '/HospitAll V1/public/index.php'],
    ['uri' => '/HospitAll V1/public/', 'script' => '/HospitAll V1/public/index.php'],
    ['uri' => '/HospitAll V1/public/login', 'script' => '/HospitAll V1/public/index.php'],
    ['uri' => '/HospitAll V1/public/api/auth/login', 'script' => '/HospitAll V1/public/index.php'],
];

foreach ($testCases as $tc) {
    echo "URI: {$tc['uri']}\n";
    echo "Result URL: " . $router->getUrl($tc['uri'], $tc['script']) . "\n\n";
}
