<?php

namespace App\Console\Commands;

use App\Models\AdgebraPartner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateAdgebraPartner extends Command
{
    protected $signature = 'adgebra:create-partner {username? : Partner login username}';

    protected $description = 'Create or reset the Adgebra partner dashboard login';

    public function handle(): int
    {
        $username = trim((string) ($this->argument('username') ?: $this->ask('Partner username')));

        if ($username === '' || !preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username)) {
            $this->error('Username must be 3-80 characters and use letters, numbers, dot, underscore or hyphen.');
            return self::FAILURE;
        }

        $password = (string) $this->secret('Set a password (minimum 12 characters)');
        $confirmation = (string) $this->secret('Confirm password');

        if (Str::length($password) < 12 || !hash_equals($password, $confirmation)) {
            $this->error('Password confirmation failed or password is shorter than 12 characters.');
            return self::FAILURE;
        }

        AdgebraPartner::updateOrCreate(
            ['username' => $username],
            ['password' => Hash::make($password), 'is_active' => true]
        );

        $this->info('Adgebra partner login saved successfully. Share the username and password through a secure channel.');
        return self::SUCCESS;
    }
}
