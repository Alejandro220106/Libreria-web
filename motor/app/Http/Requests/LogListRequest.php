<?php

namespace App\Http\Requests;

use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use Illuminate\Validation\Rule;

/**
 * GET /bitacora: contract pagination plus the log filters (C6).
 */
class LogListRequest extends ListRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'sistema' => ['sometimes', 'string', Rule::in($this->contractValues(LogSystem::cases()))],
            'estado' => ['sometimes', 'string', Rule::in($this->contractValues(LogStatus::cases()))],
            'direccion' => ['sometimes', 'string', Rule::in($this->contractValues(LogDirection::cases()))],
            'correlacion' => ['sometimes', 'string', 'max:100'],
            'desde' => ['sometimes', 'string', 'date'],
            'hasta' => ['sometimes', 'string', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'sistema.in' => 'Debe ser uno de: '.implode(', ', $this->contractValues(LogSystem::cases())).'.',
            'estado.in' => 'Debe ser uno de: '.implode(', ', $this->contractValues(LogStatus::cases())).'.',
            'direccion.in' => 'Debe ser uno de: '.implode(', ', $this->contractValues(LogDirection::cases())).'.',
            'correlacion.max' => 'Debe tener como máximo 100 caracteres.',
            'desde.date' => 'Debe ser una fecha y hora en formato ISO 8601.',
            'hasta.date' => 'Debe ser una fecha y hora en formato ISO 8601.',
        ];
    }

    /**
     * @param  array<int, LogDirection|LogStatus|LogSystem>  $cases
     * @return array<int, string>
     */
    private function contractValues(array $cases): array
    {
        return array_map(fn ($case) => $case->contract(), $cases);
    }
}
