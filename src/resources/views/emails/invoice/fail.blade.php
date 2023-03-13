{{$msg}}
<br>
<p>錯誤訊息如下</p>
@if (is_array($err))
    @foreach ($err as $e)
        @if (is_array($e))
            @foreach ($e as $v)
                {{$v}}<br>
            @endforeach
        @else
            {{$e}}
        @endif
    @endforeach
@else
    {{$err}}
@endif
