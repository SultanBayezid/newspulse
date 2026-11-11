<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Demo accounts (documented in the README) with the password "password".
     */
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Sultan Bayezid',
            'email' => 'admin@newspulse.test',
            'password' => 'password',
            'role' => 'super_admin',
            'bio' => 'Founder and lead developer of NewsPulse. Full stack engineer building modern publishing tools.',
            'email_verified_at' => now(),
        ]);

        $editor = User::create([
            'name' => 'Nadia Rahman',
            'email' => 'editor@newspulse.test',
            'password' => 'password',
            'role' => 'editor',
            'bio' => 'News editor with a decade of newsroom experience across print and digital.',
            'email_verified_at' => now(),
        ]);

        $authorA = User::create([
            'name' => 'Arif Chowdhury',
            'email' => 'author@newspulse.test',
            'password' => 'password',
            'role' => 'author',
            'bio' => 'Staff reporter covering technology, startups and the digital economy.',
            'email_verified_at' => now(),
        ]);

        $authorB = User::create([
            'name' => 'Farhana Islam',
            'email' => 'farhana@newspulse.test',
            'password' => 'password',
            'role' => 'author',
            'bio' => 'Sports journalist following cricket, football and everything in between.',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Reader Demo',
            'email' => 'reader@newspulse.test',
            'password' => 'password',
            'role' => 'subscriber',
            'email_verified_at' => now(),
        ]);
    }
}
