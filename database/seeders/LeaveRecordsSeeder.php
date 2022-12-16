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
            'type' => 1,
            'comment' => '跨年事假(5+7)',
            'start_date' => '2022-12-26',
            'end_date' => '2023-01-10',
            'start_hour' => 0,
            'end_hour' => 1,
            'hours' => 96,
            'valid_status' => 0,
        ]);
    }
}
