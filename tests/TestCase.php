<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Testes não dependem do dia em que rodam nem da internet:
        // relógio fixo numa quarta-feira útil e BrasilAPI simulada (sem feriados).
        $this->travelTo(Carbon::parse('2026-10-14 10:00:00'));
        Http::preventStrayRequests();
        Http::fake(['brasilapi.com.br/*' => Http::response($this->holidaysResponse())]);
    }

    /**
     * Feriados devolvidos pela BrasilAPI simulada. Sobrescreva para testar feriados.
     *
     * @return list<array{date: string, name: string, type: string}>
     */
    protected function holidaysResponse(): array
    {
        return [];
    }
}
