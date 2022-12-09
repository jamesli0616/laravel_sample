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
            <a href="{{ route('home') }}">
                首頁
            </a>
            <br>
            @foreach ($leaveCalendarYears as $rows)
                <a href="{{ route('showLeaveCalendarAdmin', $rows['years']) }}">
                    {{ $rows['years'] }}
                </a>/
            @endforeach
            <div id="validLeaveRecord_form" style="z-index: 1;position: fixed;margin-left:500px;border:solid;padding:3px;">
                <form action="{{ route('validLeaveRecordAdmin') }}" method="post">
                    @csrf
                    User：{{ Form::text('user_id', null, ['readonly']) }}
                    <br>
                    日期：{{ Form::text('leave_date', null, ['readonly']) }}
                    <br>
                    假日：{{ Form::select('valid_status', array(
                        '0' => $LeaveRecordsPresenter->leaveStatus(0),
                        '1' => $LeaveRecordsPresenter->leaveStatus(1),
                        '2' => $LeaveRecordsPresenter->leaveStatus(2)),
                        0
                    ) }}
                    <br>
                    <button>
                        <span>送出</span>
                    </button>
                </form>
                @foreach ($errors->all() as $error)
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $error }}</strong><br>
                    </span>
                @endforeach
            </div>
            <div class="card">
                Year: {{ $leaveRecordYear }}
                <table>
                    <tr>
                        <th>User</th><th>日期</th><th>假別</th><th>事由</th><th>時間</th><th>時長</th><th>狀態</th>
                    </tr>
                    @foreach ($leaveCalendar as $rows)
                        <tr id="{{$rows['leave_date']}}" onclick="loadValidLeaveRecord(this);">
                            <td>{{ $rows['user_id'] }}</td>
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
    function loadValidLeaveRecord(element)
    {
        let setId = $(element).find('td').eq(0).text();
        let setDate = $(element).attr('id');
        $('input[name=\'user_id\']').val(setId);
        $('input[name=\'leave_date\']').val(setDate);
    }
</script>