<?php

namespace App\Services;

use App\Models\ReservationModel;

class DashboardService
{
    protected $reservationModel;

    public function __construct()
    {
        $this->reservationModel = new ReservationModel();
    }

    public function getReservationsByStatus()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('reservations');

        $results = $builder
            ->select('status, COUNT(*) as count')
            ->where('deleted_at', null)
            ->groupBy('status')
            ->get()
            ->getResultArray();

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

    public function getReservationsStatusEvolution($startDate = null, $endDate = null)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('reservations');

        if (!$startDate) {
            $startDate = date('Y-m-01', strtotime('-6 months'));
        }
        if (!$endDate) {
            $endDate = date('Y-m-t');
        }

        $results = $builder
            ->select("DATE_FORMAT(created_at, '%Y-%m') as month, status, COUNT(*) as count")
            ->where('deleted_at', null)
            ->where('created_at >=', $startDate)
            ->where('created_at <=', $endDate)
            ->groupBy(['month', 'status'])
            ->orderBy('month', 'ASC')
            ->get()
            ->getResultArray();

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
        $db = \Config\Database::connect();
        $builder = $db->table('reservations');

        $results = $builder
            ->select('is_paid, COUNT(*) as count, SUM(total_amount) as total_amount')
            ->where('deleted_at', null)
            ->where('status !=', 'cancelled')
            ->groupBy('is_paid')
            ->get()
            ->getResultArray();

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
        $db = \Config\Database::connect();
        $builder = $db->table('reservations');

        $results = $builder
            ->select('is_invoiced, COUNT(*) as count, SUM(total_amount) as total_amount')
            ->where('deleted_at', null)
            ->where('status !=', 'cancelled')
            ->groupBy('is_invoiced')
            ->get()
            ->getResultArray();

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
        $db = \Config\Database::connect();
        $builder = $db->table('reservations r');

        $results = $builder
            ->select('s.name as jam_type, COUNT(*) as total_reservations, SUM(r.total_amount) as total_revenue')
            ->join('service_prices sp', 'r.service_price_id = sp.id')
            ->join('services s', 'sp.service_id = s.id')
            ->where('r.deleted_at', null)
            ->where('r.status !=', 'cancelled')
            ->groupBy(['s.id', 's.name'])
            ->orderBy('total_reservations', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();

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
        $db = \Config\Database::connect();
        $builder = $db->table('reservations r');

        $results = $builder
            ->select('c.name as city_name, co.name as county_name, COUNT(*) as total_events, SUM(r.total_amount) as total_revenue, AVG(r.total_amount) as avg_revenue')
            ->join('zipcodes z', 'r.zipcode_id = z.id')
            ->join('cities c', 'z.city_id = c.id')
            ->join('counties co', 'c.county_id = co.id')
            ->where('r.deleted_at', null)
            ->where('r.status !=', 'cancelled')
            ->groupBy(['c.id', 'c.name', 'co.name'])
            ->orderBy('total_events', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();

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