<?php

namespace App\Models;

use Database\Factories\InterviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'profile', 'conducted_on', 'consent_recorded', 'themes', 'notes', 'is_synthetic', 'created_by'])]
class Interview extends Model
{
    /** @use HasFactory<InterviewFactory> */
    use HasFactory;

    /** Temas usados para codificar as entrevistas na análise do diagnóstico. */
    public const THEMES = [
        'percepcao_atrasada' => 'Percepção tardia de novos chamados',
        'notificacao' => 'Notificações / alertas',
        'recuperacao_conhecimento' => 'Recuperação de soluções anteriores',
        'dependencia_pessoas' => 'Dependência de colegas experientes',
        'whatsapp' => 'Uso do WhatsApp como repositório',
        'categorizacao' => 'Categorização dos chamados',
        'recorrencia' => 'Problemas recorrentes',
        'priorizacao' => 'Priorização',
        'comunicacao_solicitante' => 'Comunicação com o solicitante',
        'ferramenta_terceiros' => 'Limitações do sistema de terceiros',
    ];

    protected function casts(): array
    {
        return [
            'conducted_on' => 'date',
            'consent_recorded' => 'boolean',
            'themes' => 'array',
            'is_synthetic' => 'boolean',
        ];
    }

    public static function nextCode(): string
    {
        $last = static::query()->where('code', 'like', 'E%')->pluck('code')
            ->map(fn (string $c) => (int) substr($c, 1))->max() ?? 0;

        return 'E'.str_pad((string) ($last + 1), 2, '0', STR_PAD_LEFT);
    }
}
