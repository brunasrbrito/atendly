<?php

namespace App\Support;

use App\Models\Requester;

/**
 * Gera chamados SINTÉTICOS a partir do catálogo em database/seeders/data/scenarios.php.
 * Usado pelo seeder (histórico) e pelo comando atendly:simular (chegada de novos chamados).
 */
class SyntheticTicketGenerator
{
    /** @var array<string, mixed>|null */
    private static ?array $catalog = null;

    /** @return array<string, mixed> */
    public static function catalog(): array
    {
        return self::$catalog ??= require database_path('seeders/data/scenarios.php');
    }

    /** @return array<string, mixed> */
    public function pickScenario(): array
    {
        $scenarios = self::catalog()['scenarios'];
        $roll = mt_rand(1, array_sum(array_column($scenarios, 'weight')));

        foreach ($scenarios as $scenario) {
            $roll -= $scenario['weight'];
            if ($roll <= 0) {
                return $scenario;
            }
        }

        return $scenarios[0];
    }

    /**
     * Payload no mesmo formato aceito pelo webhook de integração.
     *
     * @param  array<string, mixed>|null  $scenario
     * @return array<string, mixed>
     */
    public function payload(?array $scenario = null, ?Requester $requester = null): array
    {
        $scenario ??= $this->pickScenario();
        $departments = self::catalog()['departments'];
        $code = str_pad((string) mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

        return [
            'external_id' => 'CH-'.strtoupper(bin2hex(random_bytes(4))),
            'title' => $this->pick($scenario['titles']),
            'description' => $this->pick($scenario['descriptions']),
            'category' => $scenario['category'],
            'priority' => $scenario['priority'],
            'requester' => $requester
                ? ['external_id' => $requester->external_id, 'name' => $requester->name, 'department' => $requester->department]
                : ['external_id' => "SOL-{$code}", 'name' => "Solicitante {$code}", 'department' => $this->pick($departments)],
            'is_synthetic' => true,
        ];
    }

    /**
     * @template T
     *
     * @param  list<T>  $items
     * @return T
     */
    public function pick(array $items): mixed
    {
        return $items[array_rand($items)];
    }
}
