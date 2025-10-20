<?php
use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => $_ENV['DB_CONNECTION'],
    'database' => __DIR__ . '/../' . $_ENV['DB_PATH'],
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

return $capsule;
