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
            'start_hour' => 0,
            'end_hour' => 0,
            'period' => 4,
            'valid_status' => 0,
        ]);
        DB::table('leave_records')->insert([
            'user_id' => 1,
            'type' => 0,
            'comment' => 'sample leaveB',
            'start_date' => '2022-02-03',
            'end_date' => '2022-02-03',
            'start_hour' => 0,
            'end_hour' => 1,
            'period' => 8,
            'valid_status' => 0,
        ]);
        DB::table('leave_records')->insert([
            'user_id' => 1,
            'type' => 0,
            'comment' => 'sample leaveC',
            'start_date' => '2022-03-03',
            'end_date' => '2022-03-03',
            'start_hour' => 0,
            'end_hour' => 0,
            'period' => 4,
            'valid_status' => 0,
        ]);
    }
}
