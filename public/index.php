<?php
use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as DB;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/StringService.php';
require __DIR__ . '/../src/Controllers/StringController.php';

$capsule = require __DIR__ . '/../src/Database.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// POST /strings
$app->post('/strings', [StringController::class, 'create']);

$app->run();
