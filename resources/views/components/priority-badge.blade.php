@props(['priority'])
<span class="priority priority--{{ $priority->value }}" title="Prioridade {{ mb_strtolower($priority->label()) }}">
    <span class="priority__bars" aria-hidden="true"><i></i><i></i><i></i></span>{{ $priority->label() }}
</span>
