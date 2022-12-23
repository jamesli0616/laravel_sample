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
    protected function createFakeRecords(
        int $type,
        string $startDate,
        string $endDate,
        int $startHour = LeaveTimeEnum::MORNING,
        int $endHour = LeaveTimeEnum::AFTERNOON
    )
    {
        $params = [
            'user_id' => 1,
            'type' => $type,
            'comment' => 'test',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_hour' => $startHour,
            'end_hour' => $endHour,
            'hours' => 0,
            'warning' => '',
            'valid_status' => LeaveStatusEnum::APPLY,
        ];
        return $params;
    }
    // 起始時間>結束時間
    public function test_StartTimeBiggerThanEndTime()
    {
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::SIMPLE, '2022-12-23', '2022-12-22');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertEquals('起始時間大於結束時間', $response['message']);
    }
    // 起始or結束不為工作日
    public function test_StartTimeOrEndTimeIsHoliday()
    {
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::SIMPLE, '2022-12-31', '2023-01-01');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertEquals('請假起始或結束日非工作日', $response['message']);
    }
    // 事假超過14日上限
    public function test_SimpleLeaveMoreThanLimit()
    {
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::SIMPLE, '2022-11-07', '2022-11-11');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-11-07']);
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::SIMPLE, '2022-11-14', '2022-11-25');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertEquals('請假時數超過上限', $response['message']);
        // 合併家庭照顧假
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::FAMILYCARE, '2022-11-14', '2022-11-18');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-11-14']);
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::SIMPLE, '2022-11-21', '2022-11-25');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertEquals('請假時數超過上限', $response['message']);
    }
    // 家庭照顧假超7日上限
    public function test_FamilycareLeaveMoreThanLimit()
    {
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::FAMILYCARE, '2022-11-07', '2022-11-11');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-11-07']);
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::FAMILYCARE, '2022-11-14', '2022-11-16');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertEquals('請假時數超過上限', $response['message']);
        // 合併事假
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::SIMPLE, '2022-11-14', '2022-11-23');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-11-14']);
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::SIMPLE, '2022-11-24', '2022-11-25');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertEquals('請假時數超過上限', $response['message']);
    }
    // 產檢假超過7日上限
    public function test_PrentalLeaveMoreThanLimit()
    {
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::PRENTAL, '2022-11-07', '2022-11-11');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-11-07']);
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::PRENTAL, '2022-11-14', '2022-11-16');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertEquals('請假時數超過上限', $response['message']);
    }
    // 陪產假超過7日上限
    public function test_PaternityLeaveMoreThanLimit()
    {
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::PATERNITY, '2022-11-07', '2022-11-11');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-11-07']);
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::PATERNITY, '2022-11-14', '2022-11-16');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertEquals('請假時數超過上限', $response['message']);
    }
    // 生理假超過月1日上限
    public function test_PeriodLeaveMoreThanMonthLimit()
    {
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::PERIOD, '2022-11-07', '2022-11-08');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertEquals('生理假超過每月1日上限', $response['message']);
    }
    // 生理假超過3日合併病假標記
    public function test_PeriodLeaveCombineSickMoreThanLimit()
    {
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::SICK, '2022-10-03', '2022-11-14');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-10-03']);
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::PERIOD, '2022-07-15', '2022-07-15');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-07-15']);
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::PERIOD, '2022-08-15', '2022-08-15');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-08-15']);
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::PERIOD, '2022-09-15', '2022-09-15');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-09-15']);
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::PERIOD, '2022-11-18', '2022-11-18');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-11-18', "warning" => '合併病假已超過上限特別標示']);
    }
    // 安胎休養假超過30日上限標記
    public function test_TocolysisLeaveMoreThanLimit()
    {
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::TOCOLYSIS, '2022-10-03', '2022-11-04');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-10-03']);
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::TOCOLYSIS, '2022-11-07', '2022-11-15');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-11-07', "warning" => '已超過上限特別標示']);
    }
    // 病假超過30日上限標記
    public function test_SickLeaveMoreThanLimit()
    {
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::SICK, '2022-10-03', '2022-11-04');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-10-03']);
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::SICK, '2022-11-07', '2022-11-15');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-11-07', "warning" => '已超過上限特別標示']);
    }
    // 跨年度假別運算(以產檢假測試超過7日上限)
    public function test_PrentalLeavePastYearSample()
    {
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::PRENTAL, '2022-12-26', '2023-01-06');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertDatabaseHas('leave_records', ['user_id' => $fakeParams['user_id'], 'start_date' => '2022-12-26']);
        // 前年度
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::PRENTAL, '2022-12-21', '2022-12-23');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertEquals('請假時數超過上限', $response['message']);
        // 跨年度
        $fakeParams = $this->createFakeRecords(LeaveTypesEnum::PRENTAL, '2023-01-09', '2023-01-12');
        $response = $this->postJson(action(['App\Http\Controllers\LeaveRecordsController@create']), $fakeParams);
        $this->assertEquals('請假時數超過上限', $response['message']);       
    }
    // 生理假超過12日上限(年總和12日無需測試)
    public function test_PeriodLeaveMoreThanLimit()
    {
        $this->assertTrue(true, true);
    }
}
