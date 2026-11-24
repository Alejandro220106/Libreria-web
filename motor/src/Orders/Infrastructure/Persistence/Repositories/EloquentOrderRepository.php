<?php

namespace Src\Orders\Infrastructure\Persistence\Repositories;

use Carbon\CarbonImmutable;
use Src\Orders\Domain\Contracts\OrderRepository;
use Src\Orders\Domain\Entities\Order;
use Src\Orders\Infrastructure\Persistence\Models\Order as OrderModel;

class EloquentOrderRepository implements OrderRepository
{
    public function find(int $id): ?Order
    {
        $model = OrderModel::find($id);

        return $model === null ? null : $this->toEntity($model);
    }

    public function findByWooId(int $wooId): ?Order
    {
        $model = OrderModel::where('woo_id', $wooId)->first();

        return $model === null ? null : $this->toEntity($model);
    }

    public function saveFromStore(int $wooId, array $data, CarbonImmutable $orderedAt, bool $resetAddress): Order
    {
        $model = OrderModel::firstOrNew(['woo_id' => $wooId]);

        $model->fill([
            'status' => $data['status'],
            'currency' => $data['currency'],
            'total' => $data['total'],
            'lines' => $data['lines'],
            'address_text' => $data['addressText'],
            'source_updated_at' => $data['sourceUpdatedAt'],
            'synced_at' => $data['syncedAt'],
        ]);

        if (! $model->exists) {
            $model->ordered_at = $orderedAt;
        }

        if ($resetAddress) {
            $model->address_status = 'unprocessed';
            $model->address_normalized = null;
        }

        $model->save();

        // Read it back, so the columns filled by the database (defaults) are included.
        return $this->toEntity($model->refresh());
    }

    public function markAddressNormalized(int $id, array $address): void
    {
        OrderModel::whereKey($id)->first()?->update(['address_status' => 'normalized', 'address_normalized' => $address]);
    }

    public function markAddressPendingReview(int $id): void
    {
        OrderModel::whereKey($id)->first()?->update(['address_status' => 'pending_review', 'address_normalized' => null]);
    }

    public function saveConnectorResult(int $id, string $status, ?string $detail, ?string $reference, CarbonImmutable $at): void
    {
        OrderModel::whereKey($id)->first()?->update([
            'connector_status' => $status,
            'connector_detail' => $detail,
            'connector_reference' => $reference,
            'connector_updated_at' => $at,
        ]);
    }

    private function toEntity(OrderModel $model): Order
    {
        return new Order(
            id: $model->id,
            wooId: $model->woo_id,
            status: $model->status,
            currency: $model->currency,
            total: (string) $model->total,
            lines: $model->lines ?? [],
            orderedAt: $model->ordered_at,
            addressText: (string) $model->address_text,
            addressStatus: $model->address_status,
            addressNormalized: $model->address_normalized,
            connectorStatus: $model->connector_status,
        );
    }
}
