@props(['status'])
<span class="badge badge--dot badge--status-{{ $status->value }}">{{ $status->label() }}</span>
