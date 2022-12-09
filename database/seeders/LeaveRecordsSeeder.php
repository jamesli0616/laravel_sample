<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveRecordsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('leave_records')->truncate();

        DB::table('leave_records')->insert([
            'user_id' => 1,
            'leave_date'    => '2022-01-03',
            'leave_type' => 0,
            'leave_comment' => 'sample leave',
            'leave_start' => 9,
            'leave_period' => 4,
            'valid_status' => 0,
        ]);
        DB::table('leave_records')->insert([
            'user_id' => 1,
            'leave_date'    => '2022-01-05',
            'leave_type' => 1,
            'leave_comment' => 'sample leave',
            'leave_start' => 10,
            'leave_period' => 6,
            'valid_status' => 1,
        ]);
        DB::table('leave_records')->insert([
            'user_id' => 1,
            'leave_date'    => '2023-02-03',
            'leave_type' => 2,
            'leave_comment' => 'sample leave',
            'leave_start' => 11,
            'leave_period' => 5,
            'valid_status' => 0,
        ]);
    }
}
