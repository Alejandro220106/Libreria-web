<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One log entry in the contract shape (schema EntradaBitacora, C6).
 * It lists the exposed fields one by one, so nothing sensitive can leak by accident (C8).
 *
 * @mixin \App\Models\IntegrationLog
 */
class IntegrationLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->logged_at->toIso8601String(),
            'direccion' => $this->direction->contract(),
            'sistema' => $this->system->contract(),
            'operacion' => $this->operation,
            'estado' => $this->status->contract(),
            'codigo_http' => $this->http_code,
            'intentos' => $this->attempts,
            'duracion_ms' => $this->duration_ms,
            'correlacion' => $this->correlation_id,
            'error' => $this->error,
        ];
    }
}
