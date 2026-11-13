<div class="container">
    @isset($alertCount)
    <h4>Attention there are {{ $alertCount }} Failed Job on Buzzer</h4>
    @else
    <h4>Failed jobs on BUZZER for your subscribed channels</h4>
    <ul>
        @foreach ($channels as $channel => $failedJobs)
        <li>{{ $channel }}: {{ $failedJobs }}</li>
        @endforeach
    </ul>
    @endisset
</div>