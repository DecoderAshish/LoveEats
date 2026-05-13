<?php
declare(strict_types=1);

use App\Bootstrap\App;
use App\Support\Env;

require dirname(__DIR__) . '/app/Support/Env.php';

Env::load(dirname(__DIR__) . '/.env');

require dirname(__DIR__) . '/app/Bootstrap/App.php';

$app = App::create(dirname(__DIR__));
$response = $app->handle();
$response->send();
