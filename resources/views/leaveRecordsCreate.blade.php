@inject('LeaveRecordsPresenter', 'App\Presenters\LeaveRecordsPresenter')
<a href="{{ route('showLeaveCalendar', DATE('Y')) }}">
    請假表
</a>
<div id="createLeaveRecord_form" style="position: fixed;border:solid;padding:3px;">
    <form action="{{ route('createLeaveRecord') }}" method="post">
        @csrf
        {{ Form::hidden('user_id', Auth::user()->id) }}
        User：{{ Form::text('user_name', Auth::user()->name, ['readonly']) }}<br>
        日期：{{ Form::date('leave_date') }}<br>
        類別：{{ Form::select('leave_type', array(
            '0' => $LeaveRecordsPresenter->leaveType(0),
            '1' => $LeaveRecordsPresenter->leaveType(1),
            '2' => $LeaveRecordsPresenter->leaveType(2),
            '3' => $LeaveRecordsPresenter->leaveType(3)),
            0
        ) }}<br>
        事由：{{ Form::text('leave_comment') }}<br>
        時間：{{ Form::select('leave_start', array(
            '9' => '9',
            '10' => '10',
            '11' => '11',
            '12' => '12',
            '13' => '13',
            '14' => '14'),
            0
        ) }}<br>
        時長(小時)：{{ Form::select('leave_period', array(
            '4' => '4',
            '5' => '5',
            '6' => '6',
            '7' => '7',
            '8' => '8'),
            0
        ) }}<br>
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