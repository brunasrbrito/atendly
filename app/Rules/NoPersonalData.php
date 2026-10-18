<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Impede que dados que identifiquem o participante (e-mail, telefone, CPF)
 * sejam gravados nas anotações de entrevistas.
 */
class NoPersonalData implements ValidationRule
{
    private const PATTERNS = [
        'e-mail' => '/[\w.+-]+@[\w-]+\.[\w.-]+/u',
        'CPF' => '/\b\d{3}\.?\d{3}\.?\d{3}-?\d{2}\b/',
        'telefone' => '/(\(?\d{2}\)?\s?)?9?\d{4}[-\s]?\d{4}\b/',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach (self::PATTERNS as $label => $pattern) {
            if (preg_match($pattern, (string) $value)) {
                $fail("O campo parece conter um {$label}. Entrevistas não podem ter dados que identifiquem o participante.");

                return;
            }
        }
    }
}
