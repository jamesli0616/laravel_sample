<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder {

    public function run()
    {
        DB::table('users')->truncate();
        
        DB::table('users')->insert([
            'name' => 'Debug',
            'user_type' => 0,
            'email'    => 'debug@mail.com',
            'password' => Hash::make('123456'),
        ]);
        DB::table('users')->insert([
            'name' => 'userA',
            'user_type' => 1,
            'email'    => 'userA@mail.com',
            'password' => Hash::make('123456'),
        ]);
        DB::table('users')->insert([
            'name' => 'userB',
            'user_type' => 0,
            'email'    => 'userB@mail.com',
            'password' => Hash::make('123456'),
        ]);
    }
}