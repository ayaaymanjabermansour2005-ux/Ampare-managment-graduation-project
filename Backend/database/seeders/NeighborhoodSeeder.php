<?php

namespace Database\Seeders;

use App\Models\Neighborhood;
use Illuminate\Database\Seeder;

class NeighborhoodSeeder extends Seeder
{
    public function run(): void
    {
        $neighborhoods = [
            'الرمال',
            'الشجاعية',
            'الزيتون',
            'النصر',
            'تل الهوى',
            'الدرج',
            'التفاح',
            'الصبرة',
        ];

        foreach ($neighborhoods as $name) {
            Neighborhood::firstOrCreate(['name' => $name]);
        }
    }
}
