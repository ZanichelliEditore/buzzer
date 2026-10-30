<div class="container">
    <h4>Failed jobs on BUZZER for your subscribed channels</h4>
    <ul>
        @foreach ($channels as $channel => $count)
            <li>{{ $channel }}: {{ $count }}</li>
        @endforeach
    </ul>
</div>
