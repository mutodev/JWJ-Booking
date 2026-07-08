<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Ramsey\Uuid\Uuid;

class SuffolkZipcodesUseNassauPricingSeeder extends Seeder
{
    private const NASSAU_COUNTY = 'Nassau County';
    private const SUFFOLK_COUNTY = 'Suffolk County';

    private array $cityZipcodes = [
        'Cold Spring Harbor' => ['11724'],
        'Huntington' => ['11721', '11743', '11740'],
        'Melville' => ['11747', '11775', '11735', '11746'],
    ];

    public function run()
    {
        $nassauCounty = $this->findCounty(self::NASSAU_COUNTY);
        $suffolkCounty = $this->findCounty(self::SUFFOLK_COUNTY);

        if (!$nassauCounty || !$suffolkCounty) {
            echo "Missing Nassau County or Suffolk County. Seeder skipped.\n";
            return;
        }

        $this->db->transStart();

        foreach ($this->cityZipcodes as $cityName => $zipcodes) {
            $cityId = $this->ensureCity($cityName, $suffolkCounty->id);

            foreach ($zipcodes as $zipcode) {
                $updated = $this->updateExistingSuffolkZipcodes(
                    $zipcode,
                    $cityId,
                    $nassauCounty->id,
                    $suffolkCounty->id
                );

                echo "{$zipcode}: {$updated} Suffolk zipcode record(s) configured to use Nassau pricing.\n";
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            echo "Suffolk zipcodes to Nassau pricing seeder failed.\n";
            return;
        }

        echo "Suffolk zipcodes now use Nassau pricing without duplicate zipcode records.\n";
    }

    private function findCounty(string $name): ?object
    {
        return $this->db->table('counties')
            ->where('name', $name)
            ->get()
            ->getRow();
    }

    private function ensureCity(string $name, string $countyId): string
    {
        $city = $this->db->table('cities')
            ->where('name', $name)
            ->where('county_id', $countyId)
            ->get()
            ->getRow();

        if ($city) {
            $this->db->table('cities')
                ->where('id', $city->id)
                ->update([
                    'is_active' => true,
                    'deleted_at' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return $city->id;
        }

        $cityId = Uuid::uuid4()->toString();

        $this->db->table('cities')->insert([
            'id' => $cityId,
            'name' => $name,
            'county_id' => $countyId,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ]);

        return $cityId;
    }

    private function updateExistingSuffolkZipcodes(
        string $zipcode,
        string $cityId,
        string $nassauCountyId,
        string $suffolkCountyId
    ): int {
        $rows = $this->db->query("
            SELECT z.id
            FROM zipcodes z
            INNER JOIN cities ci ON ci.id = z.city_id
            WHERE z.zipcode = ?
              AND ci.county_id = ?
        ", [$zipcode, $suffolkCountyId])->getResult();

        if (empty($rows)) {
            echo "{$zipcode}: no existing Suffolk zipcode record found. No duplicate created.\n";
            return 0;
        }

        $ids = array_map(static fn ($row) => $row->id, $rows);

        $this->db->table('zipcodes')
            ->whereIn('id', $ids)
            ->update([
                'city_id' => $cityId,
                'zone_type' => 'standard',
                'override_county_id' => $nassauCountyId,
                'travel_fee_1_performer' => null,
                'travel_fee_2_performers' => null,
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return count($ids);
    }
}
