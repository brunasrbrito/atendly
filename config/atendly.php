<?php

return [

    /*
    | Token compartilhado com o sistema de chamados de terceiros. Os chamados
    | recebidos via webhook precisam enviar o header "Authorization: Bearer <token>".
    */
    'integration_token' => env('ATENDLY_INTEGRATION_TOKEN'),

    // Intervalo, em segundos, em que a fila consulta novos chamados.
    'poll_seconds' => (int) env('ATENDLY_POLL_SECONDS', 15),

    // Minutos sem um técnico assumir até o chamado ser destacado como atrasado.
    'ack_alert_minutes' => (int) env('ATENDLY_ACK_ALERT_MINUTES', 15),

    // Exibe o selo "Teste" nos registros gerados e o aviso no painel de indicadores.
    // Desligado para apresentações em que os dados são anunciados como demo.
    'show_test_marks' => (bool) env('ATENDLY_SHOW_TEST_MARKS', false),

];
