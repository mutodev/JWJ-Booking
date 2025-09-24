<?php

namespace App\Repositories;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class DashboardRepository
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Get reservations count grouped by status
     *
     * @return array
     */
    public function getReservationsByStatus(): array
    {
        $builder = $this->db->table('reservations');

        return $builder
            ->select('status, COUNT(*) as count')
            ->where('deleted_at', null)
            ->groupBy('status')
            ->get()
            ->getResultArray();
    }

    /**
     * Get reservations status evolution over time
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getReservationsStatusEvolution(string $startDate, string $endDate): array
    {
        $builder = $this->db->table('reservations');

        return $builder
            ->select("DATE_FORMAT(created_at, '%Y-%m') as month, status, COUNT(*) as count")
            ->where('deleted_at', null)
            ->where('created_at >=', $startDate)
            ->where('created_at <=', $endDate)
            ->groupBy(['month', 'status'])
            ->orderBy('month', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get payment status statistics
     *
     * @return array
     */
    public function getPaymentStatusData(): array
    {
        $builder = $this->db->table('reservations');

        return $builder
            ->select('is_paid, COUNT(*) as count, SUM(total_amount) as total_amount')
            ->where('deleted_at', null)
            ->where('status !=', 'cancelled')
            ->groupBy('is_paid')
            ->get()
            ->getResultArray();
    }

    /**
     * Get invoice status statistics
     *
     * @return array
     */
    public function getInvoiceStatusData(): array
    {
        $builder = $this->db->table('reservations');

        return $builder
            ->select('is_invoiced, COUNT(*) as count, SUM(total_amount) as total_amount')
            ->where('deleted_at', null)
            ->where('status !=', 'cancelled')
            ->groupBy('is_invoiced')
            ->get()
            ->getResultArray();
    }

    /**
     * Get most popular JAM types with statistics
     *
     * @param int $limit
     * @return array
     */
    public function getMostPopularJamTypesData(int $limit = 10): array
    {
        $builder = $this->db->table('reservations r');

        return $builder
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
    }

    /**
     * Get cities with most events data
     *
     * @param int $limit
     * @return array
     */
    public function getCitiesWithMostEventsData(int $limit = 10): array
    {
        $builder = $this->db->table('reservations r');

        return $builder
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
    }
}