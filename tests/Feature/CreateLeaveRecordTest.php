<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use App\Enums\LeaveTypesEnum;
use App\Enums\LeaveTimeEnum;
use App\Enums\LeaveStatusEnum;


class CreateLeaveRecordTest extends TestCase
{
    use WithoutMiddleware;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }
    // 建立測試假單
    protected function create_fake_records(
        int $type,
        string $start_date,
        string $end_date,
        int $start_hour = LeaveTimeEnum::MORNING,
        int $end_hour = LeaveTimeEnum::AFTERNOON
    )
    {
        $params = [
            'user_id' => 1,
            'type' => $type,
            'comment' => 'test',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start_hour' => $start_hour,
            'end_hour' => $end_hour,
            'hours' => 0,
            'warning' => '',
            'valid_status' => LeaveStatusEnum::APPLY,
        ];
        return $params;
    }
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_leave_record()
    {
        $fake_params = $this->create_fake_records(LeaveTypesEnum::SIMPLE, '2022-12-23', '2022-12-22');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertEquals('起始時間大於結束時間', $response['message']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::SIMPLE, '2022-12-31', '2023-01-01');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertEquals('請假起始或結束日為假日', $response['message']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::SIMPLE, '2022-12-22', '2022-12-29');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-12-22']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::SIMPLE, '2022-12-16', '2022-12-27');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertEquals('請假日期與其他假單重疊', $response['message']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::SIMPLE, '2022-12-30', '2023-01-06');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-12-30']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::SIMPLE, '2022-11-10', '2022-11-21');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertEquals('請假時數超過上限', $response['message']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::SIMPLE, '2022-11-10', '2022-11-18');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-11-10']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::PERIOD, '2022-09-16', '2022-09-16');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-09-16']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::SPECIAL, '2022-03-29', '2022-04-06');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-03-29']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::PERIOD, '2022-09-15', '2022-09-15');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertEquals('生理假超過每月1日上限', $response['message']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::FAMILYCARE, '2022-08-03', '2022-08-03');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertEquals('合併事假時數超過上限', $response['message']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::SICK, '2022-04-07', '2022-05-18', LeaveTimeEnum::AFTERNOON);
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-04-07']);
        $fake_params['end_hour'] = LeaveTimeEnum::AFTERNOON;

        $fake_params = $this->create_fake_records(LeaveTypesEnum::PERIOD, '2022-06-16', '2022-06-16');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-06-16']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::PERIOD, '2022-07-19', '2022-07-19');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-07-19']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::PERIOD, '2022-11-21', '2022-11-21');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-11-21', "warning" => '合併病假已超過上限特別標示']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::TOCOLYSIS, '2022-05-19', '2022-06-10');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-05-19']);

        $fake_params = $this->create_fake_records(LeaveTypesEnum::TOCOLYSIS, '2022-07-25', '2022-08-22');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fake_params);
        $this->assertDatabaseHas('leave_records', ['user_id' => 1, 'start_date' => '2022-07-25', "warning" => '已超過上限特別標示']);
    }
}
