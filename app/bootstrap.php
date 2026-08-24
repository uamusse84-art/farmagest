<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Config;
use App\Core\ErrorHandler;
use App\Core\Session;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/app/Core/Autoloader.php';
Autoloader::registar('App\\', BASE_PATH . '/app');

require_once BASE_PATH . '/app/Core/helpers.php';

Config::carregar(require BASE_PATH . '/config/config.php');

date_default_timezone_set((string) Config::obter('app.fuso', 'Africa/Maputo'));
setlocale(LC_TIME, 'pt_PT.UTF-8', 'pt_PT', 'Portuguese');

ErrorHandler::registar();

if (PHP_SAPI !== 'cli') {
    Session::iniciar();
}
