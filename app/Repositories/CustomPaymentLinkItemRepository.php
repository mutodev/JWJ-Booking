<?php

namespace App\Repositories;

use App\Models\CustomPaymentLinkItemModel;
use Ramsey\Uuid\Uuid;

/**
 * Ítems (add-ons / servicios personalizados) de un link de pago personalizado.
 */
class CustomPaymentLinkItemRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new CustomPaymentLinkItemModel();
    }

    /** @return array<int, array<string,mixed>> */
    public function getByLink(string $linkId): array
    {
        return $this->model
            ->where('payment_link_id', $linkId)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }

    /** @return array<string, array<int, array<string,mixed>>> Ítems agrupados por link. */
    public function getByLinks(array $linkIds): array
    {
        if ($linkIds === []) {
            return [];
        }

        $grouped = [];
        $rows = $this->model
            ->whereIn('payment_link_id', $linkIds)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
        foreach ($rows as $row) {
            $grouped[(string) $row['payment_link_id']][] = $row;
        }

        return $grouped;
    }

    /**
     * Reemplaza todos los ítems del link (solo se usa mientras está pending).
     */
    public function replaceForLink(string $linkId, array $items): void
    {
        $this->model->where('payment_link_id', $linkId)->delete();

        foreach (array_values($items) as $index => $item) {
            $this->model->insert([
                'id'              => Uuid::uuid4()->toString(),
                'payment_link_id' => $linkId,
                'item_type'       => $item['item_type'],
                'item_id'         => $item['item_id'],
                'name'            => $item['name'],
                'detail'          => $item['detail'] ?? null,
                'catalog_price'   => $item['catalog_price'],
                'price'           => $item['price'],
                'quantity'        => 1,
                'sort_order'      => $index,
            ]);
        }
    }
}
