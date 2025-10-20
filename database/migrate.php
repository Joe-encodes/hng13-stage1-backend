<?php
require __DIR__ . '/../vendor/autoload.php';
$capsule = require __DIR__ . '/../src/Database.php';

if (!$capsule::schema()->hasTable('strings')) {
    $capsule::schema()->create('strings', function ($table) {
        $table->string('id')->primary();
        $table->string('value');
        $table->json('properties');
        $table->timestamps();
    });
    echo "✅ Table 'strings' created successfully.\n";
} else {
    echo "⚠️ Table already exists.\n";
}
