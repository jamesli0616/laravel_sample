@inject('CalendarPresenter', 'App\Presenters\CalendarPresenter')
<head>
    <style>
        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
        }
    </style>
</head>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <a href="{{ route('uploadCalendar') }}">
                Return
            </a>
            <br>
            @foreach ($calendarYears as $rows)
                <a href="?year={{ $rows['years'] }}">
                    {{ $rows['years'] }}
                </a>&nbsp;
            @endforeach
            <div id="updateCalendar_form" style="z-index: 1;position: fixed;margin-left:300px;border:solid;padding:3px;display:none;">
                <form action="updateCalendar" method="post">
                    @csrf
                    日期：<input type="text" name="edit_date" readonly>
                    <br>
                    假日：{{ Form::select('holiday', array(
                        '0' => $CalendarPresenter->holiday(0),
                        '2' => $CalendarPresenter->holiday(2)),
                        0
                    ) }}
                    <br>
                    備註：{{ Form::text('comment') }}
                    <br>
                    <button>
                        <span>送出</span>
                    </button>
                    <button type="button" onclick="$('#updateCalendar_form').hide();">
                        <span>取消</span>
                    </button>
                </form>
            </div>
            <div class="card">
                <table>
                    <tr>
                        <th>西元日期</th>
                        <th>星期</th>
                        <th>是否放假</th>
                        <th>備註</th>
                    </tr>
                    @foreach ($calendarDate as $rows)
                        <tr onclick="loadCalendarDate(this);">
                            <td>{{ $rows['date'] }}</td>
                            <td>{{ $rows['weekdays'] }}</td>
                            <td>{{ $CalendarPresenter->holiday($rows['holiday']) }}</td>
                            <td>{{ $rows['comment'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script>
    function loadCalendarDate(element)
    {
        let setDate = $(element).find('td').eq(0).text();
        let setHoliday = $(element).find('td').eq(2).text();
        let setComment = $(element).find('td').eq(3).text();
        $('input[name=\'edit_date\']').val(setDate);
        $('input[name=\'comment\']').val(setComment);
        $("select option").filter(function() {
            return $(this).text() == setHoliday;
        }).prop('selected', true);
        $('#updateCalendar_form').show();
    }
</script>