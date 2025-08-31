<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CountySeeder extends Seeder
{
    public function run()
    {
        $data = [
            // --- Counties for MIAMI, BOCA AND PALM BEACH ---
            [
                'id'                   => '11a1b2c3-d4e5-46f7-8901-23456789abcd',
                'metropolitan_area_id' => 'a3f5c6d2-91b4-4c9f-9b92-1f3a6e4c9d11', // Miami, Boca and Palm Beach
                'name'                 => 'Palm Beach County',
            ],
            [
                'id'                   => '22b2c3d4-e5f6-47a8-9012-3456789abcde',
                'metropolitan_area_id' => 'a3f5c6d2-91b4-4c9f-9b92-1f3a6e4c9d11',
                'name'                 => 'Broward County',
            ],
            [
                'id'                   => '33c3d4e5-f6a7-48b9-0123-456789abcdef',
                'metropolitan_area_id' => 'a3f5c6d2-91b4-4c9f-9b92-1f3a6e4c9d11',
                'name'                 => 'Miami-Dade County',
            ],

            // --- Counties for NEW YORK ---
            [
                'id'                   => '44d4e5f6-a7b8-49ca-1234-56789abcdef0',
                'metropolitan_area_id' => 'b4d6e7f3-82c5-4a8e-8c01-2e4b7f5d8c22', // New York
                'name'                 => 'New York County',
            ],
            [
                'id'                   => '55e5f6a7-b8c9-40db-2345-6789abcdef01',
                'metropolitan_area_id' => 'b4d6e7f3-82c5-4a8e-8c01-2e4b7f5d8c22',
                'name'                 => 'Kings County',
            ],
            [
                'id'                   => '66f6a7b8-c9d0-41ec-3456-789abcdef012',
                'metropolitan_area_id' => 'b4d6e7f3-82c5-4a8e-8c01-2e4b7f5d8c22',
                'name'                 => 'Queens County',
            ],
            [
                'id'                   => '77a7b8c9-d0e1-42fd-4567-89abcdef0123',
                'metropolitan_area_id' => 'b4d6e7f3-82c5-4a8e-8c01-2e4b7f5d8c22',
                'name'                 => 'Bronx County',
            ],
            [
                'id'                   => '88b8c9d0-e1f2-430e-5678-9abcdef01234',
                'metropolitan_area_id' => 'b4d6e7f3-82c5-4a8e-8c01-2e4b7f5d8c22',
                'name'                 => 'Richmond County',
            ],
            [
                'id'                   => '99c9d0e1-f203-441f-6789-abcdef012345',
                'metropolitan_area_id' => 'b4d6e7f3-82c5-4a8e-8c01-2e4b7f5d8c22',
                'name'                 => 'Westchester County',
            ],
            [
                'id'                   => 'aa10d1e2-034f-4521-789a-bcdef0123456',
                'metropolitan_area_id' => 'b4d6e7f3-82c5-4a8e-8c01-2e4b7f5d8c22',
                'name'                 => 'Rockland County',
            ],
            [
                'id'                   => 'bb21e2f3-1450-4632-89ab-cdef01234567',
                'metropolitan_area_id' => 'b4d6e7f3-82c5-4a8e-8c01-2e4b7f5d8c22',
                'name'                 => 'Putnam County',
            ],
            [
                'id'                   => 'cc32f304-2561-4743-9abc-def012345678',
                'metropolitan_area_id' => 'b4d6e7f3-82c5-4a8e-8c01-2e4b7f5d8c22',
                'name'                 => 'Orange County',
            ],
            [
                'id'                   => 'dd430415-3672-4854-abcd-ef0123456789',
                'metropolitan_area_id' => 'b4d6e7f3-82c5-4a8e-8c01-2e4b7f5d8c22',
                'name'                 => 'Dutchess County',
            ],
            [
                'id'                   => 'ee540526-4783-4965-bcde-f0123456789a',
                'metropolitan_area_id' => 'b4d6e7f3-82c5-4a8e-8c01-2e4b7f5d8c22',
                'name'                 => 'Nassau County',
            ],
            [
                'id'                   => 'ff650637-5894-4a76-cdef-0123456789ab',
                'metropolitan_area_id' => 'b4d6e7f3-82c5-4a8e-8c01-2e4b7f5d8c22',
                'name'                 => 'Suffolk County',
            ],

            // --- Counties for NEW JERSEY ---
            [
                'id'                   => '1010aa11-bb22-cc33-dd44-ee5566778899',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33', // NEW JERSEY
                'name'                 => 'Bergen County',
            ],
            [
                'id'                   => '2020bb22-cc33-dd44-ee55-66778899aabb',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name'                 => 'Essex County',
            ],
            [
                'id'                   => '3030cc33-dd44-ee55-6677-8899aabbccdd',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name'                 => 'Hudson County',
            ],
            [
                'id'                   => '4040dd44-ee55-6677-8899-aabbccddeeff',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name'                 => 'Middlesex County',
            ],
            [
                'id'                   => '6060ff66-7788-99aa-bbcc-ddeeff001122',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name'                 => 'Passaic County',
            ],
            [
                'id'                   => '7070aa77-8899-aabb-ccdd-eeff00112233',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name'                 => 'Union County',
            ],
            [
                'id'                   => '8080bb88-99aa-bbcc-ddee-ff0011223344',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name'                 => 'Burlington County',
            ],
            [
                'id'                   => '9090cc99-aabb-ccdd-eeff-001122334455',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name'                 => 'Camden County',
            ],
            [
                'id'                   => '1111ddaa-bbcc-ddee-ff00-112233445566',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name'                 => 'Hunterdon County',
            ],
            [
                'id'                   => '1212eebb-ccdd-eeff-0011-223344556677',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name'                 => 'Mercer County',
            ],
            [
                'id'                   => '1313ffcc-ddee-ff00-1122-334455667788',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name'                 => 'Morris County',
            ],
            [
                'id'                   => '1414aadd-eeff-0011-2233-445566778899',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name'                 => 'Monmouth County', // (repeated in your list, lo incluyo igual)
            ],
            [
                'id'                   => '1515bbee-ff00-1122-3344-5566778899aa',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name'                 => 'Ocean County',
            ],
            [
                'id'                   => '1616ccff-0011-2233-4455-66778899aabb',
                'metropolitan_area_id' => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name'                 => 'Somerset County',
            ],

            // --- Counties for LOS ANGELES ---
            [
                'id'                   => '1717ddee-1122-3344-5566-778899aabbcc',
                'metropolitan_area_id' => 'd6f8a9b5-64e7-4c6f-8e23-4g6d9h7f0e44', // LOS ANGELES
                'name'                 => 'Los Angeles County',
            ],
            [
                'id'                   => '1818eeff-2233-4455-6677-8899aabbccdd',
                'metropolitan_area_id' => 'd6f8a9b5-64e7-4c6f-8e23-4g6d9h7f0e44',
                'name'                 => 'Orange County',
            ],
            [
                'id'                   => '1919ffaa-3344-5566-7788-99aabbccdde0',
                'metropolitan_area_id' => 'd6f8a9b5-64e7-4c6f-8e23-4g6d9h7f0e44',
                'name'                 => 'Riverside County',
            ],
            [
                'id'                   => '2020aabb-4455-6677-8899-aabbccddeeff',
                'metropolitan_area_id' => 'd6f8a9b5-64e7-4c6f-8e23-4g6d9h7f0e44',
                'name'                 => 'Ventura County',
            ],

            // --- Counties for WASHINGTON DC ---
            [
                'id'                   => '2121bbcc-5566-7788-99aa-bbccddeeff00',
                'metropolitan_area_id' => 'e7g9b0c6-55f8-4d5f-9f34-5h7e0i8g1f55', // WASHINGTON DC
                'name'                 => 'Northwest (NW)',
            ],
            [
                'id'                   => '2222ccdd-6677-8899-aabb-ccddeeff0011',
                'metropolitan_area_id' => 'e7g9b0c6-55f8-4d5f-9f34-5h7e0i8g1f55',
                'name'                 => 'Northeast (NE)',
            ],
            [
                'id'                   => '2323ddee-7788-99aa-bbcc-ddeeff001122',
                'metropolitan_area_id' => 'e7g9b0c6-55f8-4d5f-9f34-5h7e0i8g1f55',
                'name'                 => 'Southwest (SW)',
            ],
            [
                'id'                   => '2424eeff-8899-aabb-ccdd-eeff00112233',
                'metropolitan_area_id' => 'e7g9b0c6-55f8-4d5f-9f34-5h7e0i8g1f55',
                'name'                 => 'Southeast (SE)',
            ],
            [
                'id'                   => '2525ffaa-99aa-bbcc-ddee-ff0011223344',
                'metropolitan_area_id' => 'e7g9b0c6-55f8-4d5f-9f34-5h7e0i8g1f55',
                'name'                 => 'Maryland',
            ],
            [
                'id'                   => '2626aabb-aabb-ccdd-eeff-001122334455',
                'metropolitan_area_id' => 'e7g9b0c6-55f8-4d5f-9f34-5h7e0i8g1f55',
                'name'                 => 'Virginia',
            ],

            // --- Counties for CHICAGO ---
            [
                'id'                   => '3131bbcc-4455-6677-8899-aabbccddeeff',
                'metropolitan_area_id' => 'f8h0c1d7-46g9-4e4f-8a45-6i8f1j9h2g66', // CHICAGO
                'name'                 => 'Chicago, IL',
            ],
            [
                'id'                   => '3232ccdd-5566-7788-99aa-bbccddeeff00',
                'metropolitan_area_id' => 'f8h0c1d7-46g9-4e4f-8a45-6i8f1j9h2g66',
                'name'                 => 'DuPage County',
            ],
            [
                'id'                   => '3333ddee-6677-8899-aabb-ccddeeff0011',
                'metropolitan_area_id' => 'f8h0c1d7-46g9-4e4f-8a45-6i8f1j9h2g66',
                'name'                 => 'Lake County',
            ],
            [
                'id'                   => '3434eeff-7788-99aa-bbcc-ddeeff001122',
                'metropolitan_area_id' => 'f8h0c1d7-46g9-4e4f-8a45-6i8f1j9h2g66',
                'name'                 => 'Will County',
            ],
            [
                'id'                   => '3535ffaa-8899-aabb-ccdd-eeff00112233',
                'metropolitan_area_id' => 'f8h0c1d7-46g9-4e4f-8a45-6i8f1j9h2g66',
                'name'                 => 'Kendall County',
            ],
            [
                'id'                   => '3636aabb-99aa-bbcc-ddee-ff0011223344',
                'metropolitan_area_id' => 'f8h0c1d7-46g9-4e4f-8a45-6i8f1j9h2g66',
                'name'                 => 'McHenry County',
            ],
            [
                'id'                   => '3737bbcc-aabb-ccdd-eeff-001122334455',
                'metropolitan_area_id' => 'f8h0c1d7-46g9-4e4f-8a45-6i8f1j9h2g66',
                'name'                 => 'Kane County',
            ],
            [
                'id'                   => '3838ccdd-bbcc-ddee-ff00-112233445566',
                'metropolitan_area_id' => 'f8h0c1d7-46g9-4e4f-8a45-6i8f1j9h2g66',
                'name'                 => 'Grundy County',
            ],
            [
                'id'                   => '3939ddee-ccdd-eeff-0011-223344556677',
                'metropolitan_area_id' => 'f8h0c1d7-46g9-4e4f-8a45-6i8f1j9h2g66',
                'name'                 => 'DeKalb County',
            ],
            [
                'id'                   => '4040eeff-ddee-ff00-1122-334455667788',
                'metropolitan_area_id' => 'f8h0c1d7-46g9-4e4f-8a45-6i8f1j9h2g66',
                'name'                 => 'Kankakee County',
            ],

            // --- Counties for NASHVILLE ---
            [
                'id'                   => '4141aabb-ccdd-eeff-0011-223344556677',
                'metropolitan_area_id' => 'g9i1d2e8-37h0-4f3f-9b56-7j9g2k0i3h77', // NASHVILLE
                'name'                 => 'Davidson County',
            ],
            [
                'id'                   => '4242bbcc-ddee-ff00-1122-334455667788',
                'metropolitan_area_id' => 'g9i1d2e8-37h0-4f3f-9b56-7j9g2k0i3h77',
                'name'                 => 'Williamson County',
            ],
            [
                'id'                   => '4343ccdd-eeff-0011-2233-445566778899',
                'metropolitan_area_id' => 'g9i1d2e8-37h0-4f3f-9b56-7j9g2k0i3h77',
                'name'                 => 'Rutherford County',
            ],
            [
                'id'                   => '4444ddee-ff00-1122-3344-5566778899aa',
                'metropolitan_area_id' => 'g9i1d2e8-37h0-4f3f-9b56-7j9g2k0i3h77',
                'name'                 => 'Wilson County',
            ],
            [
                'id'                   => '4545eeff-0011-2233-4455-66778899aabb',
                'metropolitan_area_id' => 'g9i1d2e8-37h0-4f3f-9b56-7j9g2k0i3h77',
                'name'                 => 'Sumner County',
            ],
            [
                'id'                   => '4646ffaa-1122-3344-5566-778899aabbcc',
                'metropolitan_area_id' => 'g9i1d2e8-37h0-4f3f-9b56-7j9g2k0i3h77',
                'name'                 => 'Cheatham County',
            ],
            [
                'id'                   => '4747aabb-2233-4455-6677-8899aabbccdd',
                'metropolitan_area_id' => 'g9i1d2e8-37h0-4f3f-9b56-7j9g2k0i3h77',
                'name'                 => 'Robertson County',
            ],
            [
                'id'                   => '4848bbcc-3344-5566-7788-99aabbccdde0',
                'metropolitan_area_id' => 'g9i1d2e8-37h0-4f3f-9b56-7j9g2k0i3h77',
                'name'                 => 'Dickson County',
            ],
            [
                'id'                   => '4949ccdd-4455-6677-8899-aabbccddeeff',
                'metropolitan_area_id' => 'g9i1d2e8-37h0-4f3f-9b56-7j9g2k0i3h77',
                'name'                 => 'Maury County',
            ],
            [
                'id'                   => '5050ddee-5566-7788-99aa-bbccddeeff00',
                'metropolitan_area_id' => 'g9i1d2e8-37h0-4f3f-9b56-7j9g2k0i3h77',
                'name'                 => 'Trousdale County',
            ],

            // --- Counties for SAN FRANCISCO ---
            [
                'id'                   => '5151eeff-6677-8899-aabb-ccddeeff0011',
                'metropolitan_area_id' => 'h0j2e3f9-28i1-4g2f-8c67-8k0h3l1j4i88', // SAN FRANCISCO
                'name'                 => 'San Francisco County',
            ],
            [
                'id'                   => '5252ffaa-7788-99aa-bbcc-ddeeff001122',
                'metropolitan_area_id' => 'h0j2e3f9-28i1-4g2f-8c67-8k0h3l1j4i88',
                'name'                 => 'Marin County, CA',
            ],
            [
                'id'                   => '5353aabb-8899-aabb-ccdd-eeff00112233',
                'metropolitan_area_id' => 'h0j2e3f9-28i1-4g2f-8c67-8k0h3l1j4i88',
                'name'                 => 'Oakland, CA',
            ],
            [
                'id'                   => '5454bbcc-99aa-bbcc-ddee-ff0011223344',
                'metropolitan_area_id' => 'h0j2e3f9-28i1-4g2f-8c67-8k0h3l1j4i88',
                'name'                 => 'Alameda, CA',
            ],
            [
                'id'                   => '5555ccdd-aabb-ccdd-eeff-001122334455',
                'metropolitan_area_id' => 'h0j2e3f9-28i1-4g2f-8c67-8k0h3l1j4i88',
                'name'                 => 'San Mateo County, CA',
            ],
            [
                'id'                   => '5656ddee-bbcc-ddee-ff00-112233445566',
                'metropolitan_area_id' => 'h0j2e3f9-28i1-4g2f-8c67-8k0h3l1j4i88',
                'name'                 => 'Santa Clara County, CA',
            ],

        ];

        $this->db->table('counties')->insertBatch($data);
    }
}
