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
                <a href="{{ route('showLeaveCalendar', $rows['year']) }}">
                    {{ $rows['year'] }}
                </a>/
            @endforeach
            <a href="{{ route('showCreateLeaveForm') }}">
                建立假單
            </a>
            <div class="card">
                @isset($response)
                    <span style="color:red;">
                        <strong>{{ $response }}</strong><br>
                    </span>
                @endisset
                User: {{ Auth::user()->name }}
                Year: {{ $leaveRecordYear }}
                <table>
                    <tr>
                        <th>起始日</th><th>起始時間</th><th>結束日</th><th>結束時間</th><th>假別</th><th>事由</th><th>時長</th><th>狀態</th>
                    </tr>
                    @foreach ($leaveCalendar as $rows)
                        <tr>
                            <td>{{ date('m-d', strtotime($rows['start_date'])) }}</td>
                            <td>{{ $LeaveRecordsPresenter->leaveTime($rows['start_hour']) }}</td>
                            <td>{{ date('m-d', strtotime($rows['end_date'])) }}</td>
                            <td>{{ $LeaveRecordsPresenter->leaveTime($rows['end_hour']) }}</td>
                            <td>{{ $LeaveRecordsPresenter->leaveType($rows['type']) }}</td>
                            <td>{{ $rows['comment'] }}</td>
                            <td>{{ $rows['period'] }}小時</td>
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