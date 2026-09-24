<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminCrearUsuario extends Command
{
    protected $signature = 'admin:crear-usuario {email} {--name=Admin}';

    protected $description = 'Crea o actualiza la contraseña de un usuario para entrar al panel /admin';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $validator = Validator::make(['email' => $email], ['email' => ['required', 'email']]);

        if ($validator->fails()) {
            $this->error('Email invalido.');

            return self::FAILURE;
        }

        $password = $this->secret('Contraseña (no se muestra al escribir)');
        $confirmacion = $this->secret('Repite la contraseña');

        if ($password === null || strlen($password) < 8) {
            $this->error('La contraseña debe tener al menos 8 caracteres.');

            return self::FAILURE;
        }

        if ($password !== $confirmacion) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => (string) $this->option('name'), 'password' => Hash::make($password)]
        );

        $this->info("Usuario listo: {$user->email} (id {$user->id}). Ya puedes entrar en /admin/login.");

        return self::SUCCESS;
    }
}
