<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <a href="{{ route('home') }}">
                首頁
            </a>
            <div>
                <h3>上傳行事曆檔案</h3>
            </div>
            <div style="border-style: solid;margin: 1px auto;padding: 2px;" >
                <form action="{{route('uploadCalendar')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <input type="file" name="upfile" accept="csv/*">
                    </div>
                    <br>
                    <button class="btn btn-info" type="submit">Upload file</button>
                </form>
            </div>
        </div>
    </div>
</div>