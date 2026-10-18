@props(['when' => true])
@if ($when && config('atendly.show_test_marks'))
    <span class="badge badge--synthetic" title="Registro gerado para testes. Não representa um atendimento real.">Teste</span>
@endif
