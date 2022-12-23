<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class CreateLeaveRecordTest extends TestCase
{
    use WithoutMiddleware;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_leave_record()
    {
        // 建立測試假單
        $fake_params = [
            'user_id' => 1,
            'type' => 1,
            'comment' => 'test',
            'start_date' => '2022-01-01',
            'end_date' => '2022-12-31',
            'start_hour' => 0,
            'end_hour' => 1,
            'hours' => 0,
            'warning' => '',
            'valid_status' => 0,
        ];
 
        $fake_params['start_date'] = '2022-12-23';
        $fake_params['end_date'] = '2022-12-22';
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertEquals('起始時間大於結束時間', $response['message']);

        $fake_params['start_date'] = '2022-12-31';
        $fake_params['end_date'] = '2023-01-01';
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertEquals('請假起始或結束日為假日', $response['message']);

        $fake_params['start_date'] = '2022-12-22';
        $fake_params['end_date'] = '2022-12-29';
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-12-22']);

        $fake_params['start_date'] = '2022-12-16';
        $fake_params['end_date'] = '2022-12-27';
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertEquals('請假日期與其他假單重疊', $response['message']);

        $fake_params['start_date'] = '2022-12-30';
        $fake_params['end_date'] = '2023-01-06';
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-12-30']);

        $fake_params['start_date'] = '2022-11-10';
        $fake_params['end_date'] = '2022-11-21';
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertEquals('請假時數超過上限', $response['message']);

        $fake_params['start_date'] = '2022-11-10';
        $fake_params['end_date'] = '2022-11-18';
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-11-10']);

        $fake_params['type'] = 2;
        $fake_params['start_date'] = '2022-09-16';
        $fake_params['end_date'] = '2022-09-16';
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-09-16']);

        $fake_params['type'] = 11;
        $fake_params['start_date'] = '2022-03-29';
        $fake_params['end_date'] = '2022-04-06';
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-03-29']);

        $fake_params['type'] = 2;
        $fake_params['start_date'] = '2022-09-15';
        $fake_params['end_date'] = '2022-09-15';
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertEquals('生理假超過每月1日上限', $response['message']);

        $fake_params['type'] = 9;
        $fake_params['start_date'] = '2022-08-03';
        $fake_params['end_date'] = '2022-08-03';
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertEquals('合併事假時數超過上限', $response['message']);
    }
}
