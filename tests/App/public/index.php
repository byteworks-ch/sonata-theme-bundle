<?php

declare(strict_types=1);

use Byteworks\SonataThemeBundle\Tests\App\Kernel;
use Symfony\Component\HttpFoundation\Request;

// PHP's built-in web server: serve existing files (assets) directly.
if ('cli-server' === \PHP_SAPI && is_file(__DIR__.parse_url($_SERVER['REQUEST_URI'], \PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__, 3).'/vendor/autoload.php';

// APP_ENV "e2e": the browser tests, with their own database
$kernel = new Kernel($_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: 'dev', true);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
