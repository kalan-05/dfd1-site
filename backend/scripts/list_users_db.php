<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$rows = DB::table('users')
    ->select(['id', 'name', 'email', 'role'])
    ->orderBy('id')
    ->get();

foreach ($rows as $row) {
    echo "{$row->id} | {$row->name} | {$row->email} | {$row->role}" . PHP_EOL;
}

