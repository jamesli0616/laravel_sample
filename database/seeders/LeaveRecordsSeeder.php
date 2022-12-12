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
            'type' => 0,
            'comment' => 'sample leaveA',
            'start_date' => '2022-01-03',
            'end_date' => '2022-01-03',
            'start_hour' => 9,
            'end_hour' => 13,
            'period' => 4,
            'valid_status' => 0,
        ]);
        DB::table('leave_records')->insert([
            'user_id' => 1,
            'type' => 0,
            'comment' => 'sample leaveB',
            'start_date' => '2023-01-03',
            'end_date' => '2023-01-03',
            'start_hour' => 9,
            'end_hour' => 18,
            'period' => 8,
            'valid_status' => 0,
        ]);
        DB::table('leave_records')->insert([
            'user_id' => 1,
            'type' => 0,
            'comment' => 'sample leaveC',
            'start_date' => '2022-01-09',
            'end_date' => '2022-01-09',
            'start_hour' => 9,
            'end_hour' => 13,
            'period' => 4,
            'valid_status' => 0,
        ]);
    }
}
