<?php

declare(strict_types=1);

use App\Core\Request;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

/** @var App\Core\Router $router */
$router = require BASE_PATH . '/config/routes.php';

$router->despachar(Request::apartirDeGlobais());
