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
}
