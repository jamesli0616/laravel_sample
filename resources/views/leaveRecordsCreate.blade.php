@inject('LeaveRecordsPresenter', 'App\Presenters\LeaveRecordsPresenter')
<a href="{{ route('showLeaveCalendar', DATE('Y')) }}">
    請假表
</a>
<div id="createLeaveRecord_form" style="position: fixed;border:solid;padding:3px;">
    <form action="{{ route('createLeaveRecord') }}" method="post">
        @csrf
        {{ Form::hidden('user_id', Auth::user()->id) }}
        {{ Form::hidden('period', 0, ['readonly']) }}
        User：{{ Form::text('user_name', Auth::user()->name, ['readonly']) }}<br>
        起始日：{{ Form::date('start_date', DATE('Y-m-d')) }}<br>
        起始時間：{{ Form::select('start_hour', array(
            '0' => $LeaveRecordsPresenter->leaveTime(0),
            '1' => $LeaveRecordsPresenter->leaveTime(1)),
            0
        ) }}<br>
        結束日：{{ Form::date('end_date', DATE('Y-m-d')) }}<br>
        結束時間：{{ Form::select('end_hour', array(
            '0' => $LeaveRecordsPresenter->leaveTime(0),
            '1' => $LeaveRecordsPresenter->leaveTime(1)),
            1
        ) }}<br>
        類別：{{ Form::select('type', array(
            '0' => $LeaveRecordsPresenter->leaveType(0),
            '1' => $LeaveRecordsPresenter->leaveType(1),
            '2' => $LeaveRecordsPresenter->leaveType(2),
            '3' => $LeaveRecordsPresenter->leaveType(3)),
            0
        ) }}<br>
        事由：{{ Form::text('comment') }}<br>
        <button>
            <span>送出</span>
        </button>
    </form>
    @foreach ($errors->all() as $error)
        <span style="color:red;">
            <strong>{{ $error }}</strong><br>
        </span>
    @endforeach
</div>