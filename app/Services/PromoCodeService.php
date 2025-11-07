<?php

namespace App\Services;

use App\Repositories\PromoCodeRepository;
use CodeIgniter\I18n\Time;

/**
 * Servicio para gestión de códigos promocionales
 * Maneja la lógica de negocio para validación de promo codes
 */
class PromoCodeService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new PromoCodeRepository();
    }

    /**
     * Valida un código promocional
     * @param string $code Código promocional a validar
     * @return array Resultado de validación con is_valid, data y message
     */
    public function validatePromoCode($code)
    {
        try {
            // Buscar el código promocional por código
            $promoCode = $this->repo->findByCode($code);

            // Si no existe
            if (!$promoCode) {
                return [
                    'is_valid' => false,
                    'message' => 'Promo code not found'
                ];
            }

            // Verificar si está activo
            if (!$promoCode['is_active']) {
                return [
                    'is_valid' => false,
                    'message' => 'Promo code is inactive'
                ];
            }

            // Verificar fecha de inicio solo si existe
            if (!empty($promoCode['valid_from'])) {
                $now = Time::now();
                $validFrom = Time::parse($promoCode['valid_from']);

                if ($now->isBefore($validFrom)) {
                    return [
                        'is_valid' => false,
                        'message' => 'Promo code is not yet valid'
                    ];
                }
            }

            // Verificar fecha de expiración
            if (!empty($promoCode['valid_until'])) {
                $now = isset($now) ? $now : Time::now();
                $validUntil = Time::parse($promoCode['valid_until']);

                if ($now->isAfter($validUntil)) {
                    return [
                        'is_valid' => false,
                        'message' => 'Promo code has expired'
                    ];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error validating promo code: ' . $e->getMessage());
            return [
                'is_valid' => false,
                'message' => 'Error validating promo code: ' . $e->getMessage()
            ];
        }

        // Verificar límite de usos
        if ($promoCode['max_uses'] !== null) {
            $usageCount = $promoCode['times_used'] ?? 0;

            if ($usageCount >= $promoCode['max_uses']) {
                return [
                    'is_valid' => false,
                    'message' => 'Promo code usage limit reached'
                ];
            }
        }

        // Código válido - devolver información
        // Para simplificar en el frontend, siempre devolvemos discount_percentage
        // Si es fixed_amount, lo convertiremos en el backend al aplicarlo
        $discountPercentage = $promoCode['discount_type'] === 'percentage'
            ? $promoCode['discount_value']
            : 0; // Fixed amount será manejado diferente

        return [
            'is_valid' => true,
            'id' => $promoCode['id'],
            'code' => $promoCode['code'],
            'discount_type' => $promoCode['discount_type'],
            'discount_value' => $promoCode['discount_value'],
            'discount_percentage' => $discountPercentage,
            'description' => $promoCode['description'],
            'applies_to_travel_fee' => $promoCode['applies_to_travel_fee'] ?? false,
            'message' => 'Promo code is valid'
        ];
    }

    /**
     * Incrementa el contador de usos de un código promocional
     * @param string $promoCodeId ID del código promocional
     * @return bool Éxito de la operación
     */
    public function incrementUsage($promoCodeId)
    {
        return $this->repo->incrementUsage($promoCodeId);
    }

    /**
     * Obtener todos los códigos promocionales (Admin)
     * @return array Lista de todos los códigos promocionales
     */
    public function getAllPromoCodes()
    {
        return $this->repo->getAll();
    }

    /**
     * Crear un nuevo código promocional (Admin)
     * @param array $data Datos del código promocional
     * @return array Código promocional creado
     */
    public function createPromoCode(array $data)
    {
        // Validar datos requeridos
        if (empty($data['code'])) {
            throw new \Exception('Promo code is required');
        }

        if (empty($data['discount_percentage'])) {
            throw new \Exception('Discount percentage is required');
        }

        // Validar que el código no exista
        $existing = $this->repo->findByCode($data['code']);
        if ($existing) {
            throw new \Exception('Promo code already exists');
        }

        // Preparar datos
        $promoData = [
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'code' => strtoupper($data['code']),
            'discount_type' => 'percentage',
            'discount_value' => $data['discount_percentage'],
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'max_uses' => $data['max_uses'] ?? null,
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
            'description' => $data['description'] ?? null,
            'applies_to_travel_fee' => false,
            'created_at' => Time::now()->toDateTimeString(),
            'updated_at' => Time::now()->toDateTimeString(),
        ];

        $this->repo->create($promoData);

        return $promoData;
    }

    /**
     * Actualizar un código promocional (Admin)
     * @param string $id ID del código promocional
     * @param array $data Datos a actualizar
     * @return array Código promocional actualizado
     */
    public function updatePromoCode(string $id, array $data)
    {
        // Verificar que existe
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \Exception('Promo code not found');
        }

        // Preparar datos de actualización
        $updateData = [
            'discount_value' => $data['discount_percentage'] ?? $existing['discount_value'],
            'valid_from' => $data['valid_from'] ?? $existing['valid_from'],
            'valid_until' => $data['valid_until'] ?? $existing['valid_until'],
            'max_uses' => $data['max_uses'] ?? $existing['max_uses'],
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : $existing['is_active'],
            'description' => $data['description'] ?? $existing['description'],
            'updated_at' => Time::now()->toDateTimeString(),
        ];

        $this->repo->update($id, $updateData);

        return array_merge($existing, $updateData);
    }

    /**
     * Eliminar un código promocional (Admin)
     * @param string $id ID del código promocional
     * @return bool Éxito de la operación
     */
    public function deletePromoCode(string $id)
    {
        // Verificar que existe
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \Exception('Promo code not found');
        }

        // Verificar si ha sido usado
        if (isset($existing['times_used']) && $existing['times_used'] > 0) {
            throw new \Exception('Cannot delete a promo code that has been used. Consider deactivating it instead.');
        }

        return $this->repo->delete($id);
    }
}
