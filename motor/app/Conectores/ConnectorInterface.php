<?php

namespace App\Conectores;

/**
 * Common interface of every external connector (brief 6.6.1).
 *
 * TEAM-MADE PLACEHOLDER: the instructor's interface could not be found, so this one
 * was written by the team (see docs/artefactos-docente.md). Replace it, and adapt
 * the implementations, when the official file is available.
 *
 * Business code only knows this interface; the provider comes from CONECTOR_PROVEEDOR.
 */
interface ConnectorInterface
{
    /**
     * Runs the provider's action for a new order. It must not throw because the
     * provider failed: that is reported in the result, so the order stays saved.
     */
    public function execute(ConnectorOrder $order): ConnectorResult;
}
