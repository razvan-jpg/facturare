@php
    $src = asset('images/help/'.$shot.'.png');
    $path = public_path('images/help/'.$shot.'.png');
    $exists = is_file($path);
@endphp
<figure class="help-figure">
    @if($exists)
        <a href="{{ $src }}" target="_blank" rel="noopener" class="help-figure-link">
            <img src="{{ $src }}" alt="{{ $caption }}" loading="lazy">
        </a>
    @else
        <div class="help-figure-placeholder">
            <span>Captură: {{ $shot }}</span>
            <small>{{ $caption }}</small>
        </div>
    @endif
    <figcaption>
        <strong>{{ $label ?? '' }}</strong>
        {{ $caption }}
    </figcaption>
</figure>
