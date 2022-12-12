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
            <div id="validLeaveRecord_form" style="z-index: 1;position: fixed;margin-left:640px;border:solid;padding:3px;">
                <form action="{{ route('validLeaveRecordAdmin') }}" method="post">
                    @csrf
                    單號：{{ Form::text('leave_id', null, ['readonly']) }}
                    <br>
                    User：{{ Form::text('user_name', null, ['readonly']) }}
                    <br>
                    狀態：{{ Form::select('valid_status', array(
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
                        <th>User</th><th>起始日</th><th>起始時間</th><th>結束日</th><th>結束時間</th><th>假別</th><th>事由</th><th>時長</th><th>狀態</th>
                    </tr>
                    @foreach ($leaveCalendar as $rows)
                        <tr id="{{$rows['lid']}}" onclick="loadValidLeaveRecord(this);">
                            <td>{{ $rows['name'] }}</td>
                            <td>{{ date('m-d', strtotime($rows['start_date'])) }}</td>
                            <td>{{ $rows['start_hour'] }}:00</td>
                            <td>{{ date('m-d', strtotime($rows['end_date'])) }}</td>
                            <td>{{ $rows['end_hour'] }}:00</td>
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
    function loadValidLeaveRecord(element)
    {
        let setId = $(element).attr('id');
        let setName = $(element).find('td').eq(0).text();
        $('input[name=\'leave_id\']').val(setId);
        $('input[name=\'user_name\']').val(setName);
    }
</script>