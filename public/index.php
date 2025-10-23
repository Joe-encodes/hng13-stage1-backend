<?php
use Slim\Factory\AppFactory;
use App\Controllers\StringController;
use App\Controllers\FilterController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';

$capsule = require __DIR__ . '/../src/Database.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$app->get('/strings/filter-by-natural-language', [FilterController::class, 'filterByNatural']); 
$app->get('/strings', [FilterController::class, 'filter']);
$app->post('/strings', [StringController::class, 'create']); 
$app->get('/strings/{string_value}', [StringController::class, 'getByValue']);
$app->delete('/strings/{string_value}', [StringController::class, 'delete']);

// Default
$app->get('/', function (Request $request, Response $res) {
    $res->getBody()->write(json_encode(["status" => "running"], JSON_PRETTY_PRINT));
    return $res->withHeader('Content-Type', 'application/json');
});

$app->run();
