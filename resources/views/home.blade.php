<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    {{ $user_name }} are logged in!
                </div>

                <a class="dropdown-item" href="{{ route('logout') }}" onclick="logout();">
                    Logout
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