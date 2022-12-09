<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                @if( Auth::user()->user_type == 1)
                    <a class="dropdown-item" href="{{ route('showUploadCalendar' ) }}">
                        管理行事曆
                    </a>/
                    <a class="dropdown-item" href="{{ route('showLeaveCalendarAdmin', DATE('Y')) }}">
                        管理假單
                    </a>/
                @endif
                <a class="dropdown-item" href="{{ route('showCalendar', DATE('Y') ) }}">
                    行事曆
                </a>
                <div class="card-body">
                    {{ $user_name }} 已登入
                    @if( Auth::user()->user_type == 1)
                        [管理者]
                    @endif
                </div>
                <a class="dropdown-item" href="{{ route('logout') }}" onclick="logout();">
                    登出
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function logout()
{
    event.preventDefault();
    document.getElementById('logout-form').submit();
}
</script>