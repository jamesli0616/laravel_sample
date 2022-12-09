@inject('LeaveRecordsPresenter', 'App\Presenters\LeaveRecordsPresenter')
<head>
    <style>
        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
            text-align:center;
        }
        td {
            width:64px;
            height:48px;
        }
        .empty {
            background-color:grey;
        }
    </style>
</head>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <a href="{{ route('showCalendar', DATE('Y')) }}">
                行事曆
            </a>
            <br>
            @foreach ($leaveCalendarYears as $rows)
                <a href="{{ route('showLeaveCalendar', $rows['years']) }}">
                    {{ $rows['years'] }}
                </a>/
            @endforeach
            <a href="{{ route('showCreateLeaveForm') }}">
                建立假單
            </a>
            <div class="card">
                User: {{ Auth::user()->name }}
                Year: {{ $leaveRecordYear }}
                <table>
                    <tr>
                        <th>日期</th><th>假別</th><th>事由</th><th>時間</th><th>時長</th><th>狀態</th>
                    </tr>
                    @foreach ($leaveCalendar as $rows)
                        <tr>
                            <td>{{ date('m-d', strtotime($rows['leave_date'])) }}</td>
                            <td>{{ $LeaveRecordsPresenter->leaveType($rows['leave_type']) }}</td>
                            <td>{{ $rows['leave_comment'] }}</td>
                            <td>{{ $rows['leave_start'] }}:00</td>
                            <td>{{ $rows['leave_period'] }}小時</td>
                            <td>{{ $LeaveRecordsPresenter->leaveStatus($rows['valid_status']) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script>

</script>