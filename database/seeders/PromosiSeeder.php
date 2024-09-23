<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromosiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('promosis')->insert([
            [
                'uuid' => Str::uuid(),
                'nama_promosi' => 'Diskon Kemerdekaan',
                'start_date' => '2024-08-01',
                'end_date' => '2024-08-31',
                'status' => 'expired',
                'kode' => 'MERDEKA2024',
                'discount' => 0.20, // Diskon 20%
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'nama_promosi' => 'Diskon Natal',
                'start_date' => '2024-12-01',
                'end_date' => '2024-12-25',
                'status' => 'active',
                'kode' => 'NATAL2024',
                'discount' => 0.25, // Diskon 25%
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'nama_promosi' => 'Diskon Tahun Baru',
                'start_date' => '2024-12-26',
                'end_date' => '2025-01-05',
                'status' => 'upcoming',
                'kode' => 'NEWYEAR2025',
                'discount' => 0.30, // Diskon 30%
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'nama_promosi' => 'Diskon Hari Tani',
                'start_date' => '2024-09-22',
                'end_date' => '2025-09-30',
                'status' => 'upcoming',
                'kode' => 'TANIJAYA2024',
                'discount' => 0.50, // Diskon 50%
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
