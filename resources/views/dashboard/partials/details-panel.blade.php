@php
    $sections = (array) (config("dashboard.widgets.{$widgetKey}.details") ?? []);
@endphp
<div class="dc-dash-details">
    @forelse($sections as $heading => $bullets)
        <h4>{{ $heading }}</h4>
        <ul>
            @foreach((array) $bullets as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    @empty
        <p class="dc-dash-cfg__hint">{{ config("dashboard.widgets.{$widgetKey}.description") }}</p>
    @endforelse
</div>
