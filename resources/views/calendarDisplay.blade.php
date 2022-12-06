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
            <div class="card">
                <table>
                    <tr>
                        <th>西元日期</td>
                        <th>星期</td>
                        <th>是否放假</td>
                        <th>備註</td>
                    </tr>
                    @foreach ($calendarDate as $rows)
                        <tr>
                            <td>{{ $rows['date'] }}</td>
                            <td>{{ $rows['weekdays'] }}</td>
                            <td>{{ $rows['holiday'] }}</td>
                            <td>{{ $rows['comment'] }}</td>
                        </tr>
                    @endforeach
                </table>
                <a class="dropdown-item" href="{{ route('upload') }}">
                    Return
                </a>
            </div>
        </div>
    </div>
</div>