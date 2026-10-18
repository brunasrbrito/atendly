@props(['category'])
@if ($category)
    <span {{ $attributes->merge(['class' => 'tag']) }} style="--tag: {{ $category->color }}">{{ $category->name }}</span>
@else
    <span {{ $attributes->merge(['class' => 'tag subtle']) }}>Sem categoria</span>
@endif
