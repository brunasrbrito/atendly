<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Composer\CaBundle\CaBundle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Calendário de dias úteis: fins de semana + feriados nacionais da BrasilAPI.
 *
 * GET https://brasilapi.com.br/api/feriados/v1/{ano}
 * → [{"date": "2026-11-02", "name": "Finados", "type": "national"}, ...]
 *
 * Os feriados ficam em cache por 30 dias. Se a API estiver fora do ar, o sistema
 * continua funcionando só com a regra de fim de semana e tenta de novo em 10 minutos.
 */
class BusinessCalendar
{
    private const CACHE_DAYS = 30;

    private const RETRY_MINUTES = 10;

    /**
     * Feriados nacionais do ano, indexados pela data (Y-m-d).
     *
     * @return array<string, string>
     */
    public function holidays(int $year): array
    {
        $key = "atendly:feriados:{$year}";

        if (Cache::has($key)) {
            return Cache::get($key);
        }

        try {
            $holidays = Http::baseUrl(config('atendly.holidays_api'))
                ->withOptions($this->tlsOptions())
                ->acceptJson()
                ->timeout(5)
                ->retry(2, 300, throw: false)
                ->get((string) $year)
                ->throw()
                ->collect()
                ->mapWithKeys(fn (array $h) => [$h['date'] => $h['name']])
                ->all();

            Cache::put($key, $holidays, now()->addDays(self::CACHE_DAYS));

            return $holidays;
        } catch (Throwable $e) {
            Log::warning('BrasilAPI indisponível; usando apenas a regra de fim de semana.', ['ano' => $year, 'erro' => $e->getMessage()]);
            Cache::put($key, [], now()->addMinutes(self::RETRY_MINUTES));

            return [];
        }
    }

    /**
     * Instalações de PHP sem "curl.cainfo" (comum no Windows) não validam HTTPS.
     * Em vez de desligar a verificação, aponta para os certificados raiz do sistema
     * ou, na falta deles, para o pacote da Mozilla distribuído pelo composer/ca-bundle.
     *
     * @return array<string, mixed>
     */
    private function tlsOptions(): array
    {
        return ['verify' => CaBundle::getSystemCaRootBundlePath()];
    }

    /** Nome do feriado na data, ou null. */
    public function holidayName(CarbonInterface $date): ?string
    {
        return $this->holidays($date->year)[$date->toDateString()] ?? null;
    }

    /**
     * Motivo de a data não ser dia útil ("Sábado", "Feriado: Finados"), ou null se for dia útil.
     */
    public function closedReason(CarbonInterface $date): ?string
    {
        if ($date->isSaturday()) {
            return 'Sábado';
        }
        if ($date->isSunday()) {
            return 'Domingo';
        }

        $holiday = $this->holidayName($date);

        return $holiday ? "Feriado: {$holiday}" : null;
    }

    public function isBusinessDay(CarbonInterface $date): bool
    {
        return $this->closedReason($date) === null;
    }

    public function nextBusinessDay(CarbonInterface $date): Carbon
    {
        $next = Carbon::instance($date)->addDay()->startOfDay();

        for ($i = 0; $i < 30 && ! $this->isBusinessDay($next); $i++) {
            $next->addDay();
        }

        return $next;
    }

    /**
     * Situação de hoje para o registro manual de chamados.
     * Retorna null quando é permitido registrar (dia útil ou regra desligada).
     *
     * @return array{reason: string, next: Carbon}|null
     */
    public function manualRegistrationBlock(?CarbonInterface $now = null): ?array
    {
        if (! config('atendly.block_non_business_days')) {
            return null;
        }

        $now ??= now();
        $reason = $this->closedReason($now);

        return $reason ? ['reason' => $reason, 'next' => $this->nextBusinessDay($now)] : null;
    }
}
