<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder {

    public function run()
    {
        DB::table('users')->delete();
        
        User::create([
            'name' => 'Debug',
            'email'    => 'debug@mail.com',
            'password' => Hash::make('123456'),
        ]);
    }
}