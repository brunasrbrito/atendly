@props(['name', 'size' => null])
@php($initials = collect(preg_split('/\s+/', trim((string) $name)))->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode(''))
<span {{ $attributes->class(['avatar', 'avatar--'.$size => $size]) }} title="{{ $name }}" aria-hidden="true">{{ $initials }}</span>
