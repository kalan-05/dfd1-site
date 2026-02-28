<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$email = $argv[1] ?? null;
$role = $argv[2] ?? null;

if (! is_string($email) || ! is_string($role) || $email === '' || $role === '') {
    fwrite(STDERR, "Usage: php set_user_role.php <email> <role>\n");
    exit(1);
}

$allowed = ['super_admin', 'admin', 'editor'];
if (! in_array($role, $allowed, true)) {
    fwrite(STDERR, "Invalid role. Allowed: super_admin, admin, editor\n");
    exit(1);
}

$updated = User::query()
    ->where('email', $email)
    ->update(['role' => $role]);

echo "updated={$updated}\n";

