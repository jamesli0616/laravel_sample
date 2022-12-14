@inject('CalendarPresenter', 'App\Presenters\CalendarPresenter')
<head>
    <style>
        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
            text-align:center;
        }
        td {
            width:48px;
            height:48px;
        }
        .empty {
            background-color:grey;
        }
        .date_back {
            line-height: 10px;
            display: block;
            color: #bbb;
        }
        .date_front {
            position: absolute;
            width:48px;
            margin-top: -24px;
        }
    </style>
</head>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <a href="{{ route('home') }}">
                Home
            </a>
            <br>
            @foreach ($calendarYears as $rows)
                <a href="{{ route('showCalendar', $rows) }}">
                    {{ $rows }}
                </a>/
            @endforeach
            <a href="{{ route('showLeaveCalendar', DATE('Y')) }}">
                請假表
            </a>
            @if( Auth::user()->user_type == 1 )
                <div id="updateCalendar_form" style="z-index: 1;position: fixed;margin-left:380px;border:solid;padding:3px;display:none;">
                    <form action="{{ route('updateCalendar') }}" method="post">
                        @csrf
                        日期：{{ Form::text('edit_date', null, ['readonly']) }}
                        <br>
                        假日：{{ Form::select('holiday', array(
                            '0' => $CalendarPresenter->holiday(0),
                            '1' => $CalendarPresenter->holiday(1),
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
            @endif
            <div class="card">
                <table>
                    @php 
                        $month = 0
                    @endphp
                    @foreach ($calendarDate as $rows)
                        @if($month != date('m', strtotime($rows['date'])))
                            @php 
                                $month++
                            @endphp
                            <tr>
                                <th colspan="7">{{ $month }}月</th>
                            </tr>
                            <tr>
                                <th>日</th><th>一</th><th>二</th><th>三</th><th>四</th><th>五</th><th>六</th>
                            </tr>
                            <tr>
                                @for($i = 0 ; $i < date('w', strtotime($rows['date'])) - 7;$i++)
                                    <td class="empty"></td>
                                @endfor
                            </tr><tr>
                            @if(date('w', strtotime($rows['date'])) != 0)
                                @for($i = 0 ; $i < date('w', strtotime($rows['date']));$i++)
                                    <td class="empty"></td>
                                @endfor
                            @endif
                        @endif
                        <td onclick="loadCalendarDate(this);" id="{{$rows['date']}}" holiday-type="{{$rows['holiday']}}">
                            <span class="date_back">{{ date('d', strtotime($rows['date'])) }}</span>
                            <div class="date_front">{{ $rows['comment'] }}</div>
                        </td>
                        @if(date('w', strtotime($rows['date'])) == 6 )
                            </tr><tr>
                        @endif
                    @endforeach
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script>
    function loadCalendarDate(element)
    {
        let setDate = $(element).attr('id');
        let setHoliday = $(element).attr('holiday-type');
        let setComment = $(element).find('div').text();
        $('input[name=\'edit_date\']').val(setDate);
        $('input[name=\'comment\']').val(setComment);
        $("select option").filter(function() {
            return $(this).val() == setHoliday;
        }).prop('selected', true);
        $('#updateCalendar_form').show();
    }
</script>