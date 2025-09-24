<?php

namespace App\Services;

use App\Repositories\DashboardRepository;
use App\Repositories\ReservationAddonRepository;

class DashboardService
{
    protected DashboardRepository $dashboardRepository;
    protected ReservationAddonRepository $reservationAddonRepository;

    public function __construct()
    {
        $this->dashboardRepository = new DashboardRepository();
        $this->reservationAddonRepository = new ReservationAddonRepository();
    }

    public function getReservationsByStatus()
    {
        $results = $this->dashboardRepository->getReservationsByStatus();

        $statusLabels = [
            'new' => 'Nueva',
            'under_review' => 'En Revisión',
            'confirmed' => 'Confirmada',
            'cancelled' => 'Cancelada'
        ];

        $data = [];
        $total = 0;

        foreach ($results as $result) {
            $count = (int)$result['count'];
            $total += $count;

            $data[] = [
                'status' => $result['status'],
                'label' => $statusLabels[$result['status']] ?? $result['status'],
                'count' => $count,
                'color' => $this->getStatusColor($result['status'])
            ];
        }

        foreach ($data as &$item) {
            $item['percentage'] = $total > 0 ? round(($item['count'] / $total) * 100, 1) : 0;
        }

        return [
            'data' => $data,
            'total' => $total
        ];
    }

    public function getReservationsStatusEvolution()
    {
        // Automáticamente calcular últimos 6 meses
        $startDate = date('Y-m-01', strtotime('-6 months'));
        $endDate = date('Y-m-t');

        $results = $this->dashboardRepository->getReservationsStatusEvolution($startDate, $endDate);

        $statusLabels = [
            'new' => 'Nueva',
            'under_review' => 'En Revisión',
            'confirmed' => 'Confirmada',
            'cancelled' => 'Cancelada'
        ];

        $months = [];
        $statusData = [];

        foreach ($results as $result) {
            $month = $result['month'];
            $status = $result['status'];
            $count = (int)$result['count'];

            if (!in_array($month, $months)) {
                $months[] = $month;
            }

            if (!isset($statusData[$status])) {
                $statusData[$status] = [
                    'label' => $statusLabels[$status] ?? $status,
                    'color' => $this->getStatusColor($status),
                    'data' => []
                ];
            }

            $statusData[$status]['data'][$month] = $count;
        }

        sort($months);

        foreach ($statusData as $status => &$data) {
            $seriesData = [];
            foreach ($months as $month) {
                $seriesData[] = $data['data'][$month] ?? 0;
            }
            $data['data'] = $seriesData;
        }

        return [
            'months' => $months,
            'series' => array_values($statusData)
        ];
    }

    public function getPaymentStatus()
    {
        $results = $this->dashboardRepository->getPaymentStatusData();

        $data = [
            'paid' => [
                'count' => 0,
                'amount' => 0,
                'percentage' => 0,
                'label' => 'Pagadas'
            ],
            'pending' => [
                'count' => 0,
                'amount' => 0,
                'percentage' => 0,
                'label' => 'Pendientes'
            ]
        ];

        $totalCount = 0;
        $totalAmount = 0;

        foreach ($results as $result) {
            $isPaid = (int)$result['is_paid'];
            $count = (int)$result['count'];
            $amount = (float)$result['total_amount'];

            $key = $isPaid ? 'paid' : 'pending';
            $data[$key]['count'] = $count;
            $data[$key]['amount'] = $amount;

            $totalCount += $count;
            $totalAmount += $amount;
        }

        $data['paid']['percentage'] = $totalCount > 0 ? round(($data['paid']['count'] / $totalCount) * 100, 1) : 0;
        $data['pending']['percentage'] = $totalCount > 0 ? round(($data['pending']['count'] / $totalCount) * 100, 1) : 0;

        return [
            'data' => $data,
            'totals' => [
                'count' => $totalCount,
                'amount' => $totalAmount
            ]
        ];
    }

    public function getInvoiceStatus()
    {
        $results = $this->dashboardRepository->getInvoiceStatusData();

        $data = [
            'invoiced' => [
                'count' => 0,
                'amount' => 0,
                'percentage' => 0,
                'label' => 'Facturadas'
            ],
            'pending' => [
                'count' => 0,
                'amount' => 0,
                'percentage' => 0,
                'label' => 'Pendientes'
            ]
        ];

        $totalCount = 0;
        $totalAmount = 0;

        foreach ($results as $result) {
            $isInvoiced = (int)$result['is_invoiced'];
            $count = (int)$result['count'];
            $amount = (float)$result['total_amount'];

            $key = $isInvoiced ? 'invoiced' : 'pending';
            $data[$key]['count'] = $count;
            $data[$key]['amount'] = $amount;

            $totalCount += $count;
            $totalAmount += $amount;
        }

        $data['invoiced']['percentage'] = $totalCount > 0 ? round(($data['invoiced']['count'] / $totalCount) * 100, 1) : 0;
        $data['pending']['percentage'] = $totalCount > 0 ? round(($data['pending']['count'] / $totalCount) * 100, 1) : 0;

        return [
            'data' => $data,
            'totals' => [
                'count' => $totalCount,
                'amount' => $totalAmount
            ]
        ];
    }

    public function getMostPopularJamTypes($limit = 10)
    {
        $results = $this->dashboardRepository->getMostPopularJamTypesData($limit);

        $colors = [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
            '#858796', '#5a5c69', '#6f42c1', '#e83e8c', '#fd7e14'
        ];

        $data = [];
        foreach ($results as $index => $result) {
            $data[] = [
                'jam_type' => $result['jam_type'],
                'total_reservations' => (int)$result['total_reservations'],
                'total_revenue' => (float)$result['total_revenue'],
                'color' => $colors[$index % count($colors)]
            ];
        }

        return [
            'data' => $data,
            'total_reservations' => array_sum(array_column($data, 'total_reservations')),
            'total_revenue' => array_sum(array_column($data, 'total_revenue'))
        ];
    }

    public function getCitiesWithMostEvents($limit = 10)
    {
        $results = $this->dashboardRepository->getCitiesWithMostEventsData($limit);

        $colors = [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
            '#858796', '#5a5c69', '#6f42c1', '#e83e8c', '#fd7e14'
        ];

        $data = [];
        foreach ($results as $index => $result) {
            $data[] = [
                'city_name' => $result['city_name'],
                'county_name' => $result['county_name'],
                'full_name' => $result['city_name'] . ', ' . $result['county_name'],
                'total_events' => (int)$result['total_events'],
                'total_revenue' => (float)$result['total_revenue'],
                'avg_revenue' => (float)$result['avg_revenue'],
                'color' => $colors[$index % count($colors)]
            ];
        }

        return [
            'data' => $data,
            'total_events' => array_sum(array_column($data, 'total_events')),
            'total_revenue' => array_sum(array_column($data, 'total_revenue'))
        ];
    }

    /**
     * Get most popular addons analytics
     *
     * @param int $limit Maximum number of addons to return
     * @return array Analytics data with addon sales information
     */
    public function getMostPopularAddons(int $limit = 10): array
    {
        $results = $this->reservationAddonRepository->getMostPopular($limit);

        $colors = [
            '#ff6384', '#36a2eb', '#ffce56', '#4bc0c0', '#9966ff',
            '#ff9f40', '#ff6384', '#c9cbcf', '#4bc0c0', '#ff6384'
        ];

        $data = [];
        foreach ($results as $index => $result) {
            $data[] = [
                'addon_name' => $result['addon_name'],
                'addon_description' => $result['addon_description'] ?? '',
                'total_sold' => (int)$result['total_sold'],
                'total_revenue' => (float)$result['total_revenue'],
                'reservations_count' => (int)$result['reservations_count'],
                'avg_price' => (float)$result['avg_price'],
                'color' => $colors[$index % count($colors)]
            ];
        }

        return [
            'data' => $data,
            'total_addons_sold' => array_sum(array_column($data, 'total_sold')),
            'total_addon_revenue' => array_sum(array_column($data, 'total_revenue')),
            'total_reservations_with_addons' => array_sum(array_column($data, 'reservations_count'))
        ];
    }

    private function getStatusColor($status)
    {
        $colors = [
            'new' => '#17a2b8',           // Info blue
            'under_review' => '#ffc107',  // Warning yellow
            'confirmed' => '#28a745',     // Success green
            'cancelled' => '#dc3545'      // Danger red
        ];

        return $colors[$status] ?? '#6c757d';
    }
}