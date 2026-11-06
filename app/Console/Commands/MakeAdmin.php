<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeAdmin extends Command
{
    protected $signature = 'app:make-admin {email : Email akun yang akan diberi akses administrator}';

    protected $description = 'Buat atau promosikan akun administrator tanpa kredensial bawaan.';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Alamat email tidak valid.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        if (! $user->exists) {
            $name = trim((string) $this->ask('Nama administrator'));
            if ($name === '') {
                $this->error('Nama wajib diisi.');

                return self::FAILURE;
            }

            $password = $this->secret('Kata sandi (minimal 12 karakter)');
            $confirmation = $this->secret('Ulangi kata sandi');
            if (strlen((string) $password) < 12 || $password !== $confirmation) {
                $this->error('Kata sandi harus minimal 12 karakter dan kedua isian harus cocok.');

                return self::FAILURE;
            }

            $user->name = $name;
            $user->password = Hash::make($password);
        }

        $user->is_admin = true;
        $user->save();
        $this->info('Akses administrator diberikan kepada '.$user->email.'.');

        return self::SUCCESS;
    }
}
