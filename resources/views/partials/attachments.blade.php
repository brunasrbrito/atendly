@if ($attachments->isNotEmpty())
    <div class="attachments">
        @foreach ($attachments as $file)
            @php($url = \Illuminate\Support\Facades\Storage::disk('public')->url($file->path))
            @if ($file->kind === 'imagem')
                <a class="attachments__image" href="{{ $url }}" target="_blank" rel="noopener" title="{{ $file->original_name }}">
                    <img src="{{ $url }}" alt="{{ $file->original_name }}" loading="lazy">
                </a>
            @elseif ($file->kind === 'audio')
                <audio controls preload="none" src="{{ $url }}" title="{{ $file->original_name }}"></audio>
            @else
                <a class="btn btn--sm" href="{{ $url }}" target="_blank" rel="noopener"><x-icon name="paperclip" /> {{ $file->original_name }}</a>
            @endif
        @endforeach
    </div>
@endif
