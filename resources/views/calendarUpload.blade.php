<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <form action="{{route('upload')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <input type="file" name="upfile" accept="csv/*">
                    </div>
                    <button class="btn btn-info" type="submit">Upload file</button>
                </form>
                @isset($message)
                    <h4>{{$message}}</h4>
                @endisset
            </div>
        </div>
    </div>
</div>