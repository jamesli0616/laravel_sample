<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <table>
                    @foreach ($csvContents as $rows)
                        <tr><td>{{ $rows }}</td></tr>
                    @endforeach
                </table>
                <a class="dropdown-item" href="{{ route('calendar') }}">
                    Return
                </a>
            </div>
        </div>
    </div>
</div>