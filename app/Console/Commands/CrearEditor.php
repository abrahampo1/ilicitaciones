<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CrearEditor extends Command
{
    protected $signature = 'app:crear-editor
                            {--name= : Nombre del editor}
                            {--email= : Email de acceso}
                            {--password= : Contraseña (se pedirá si se omite)}';

    protected $description = 'Crea (o promueve) un usuario editor del periódico';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nombre');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Contraseña');

        if (! $email || ! $password) {
            $this->error('Email y contraseña son obligatorios.');

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name ?: 'Editor', 'password' => Hash::make($password), 'is_editor' => true],
        );

        $this->info("Editor listo: {$user->email} (id {$user->id})");

        return self::SUCCESS;
    }
}
