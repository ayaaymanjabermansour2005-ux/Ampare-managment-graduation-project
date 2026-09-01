<?php

namespace Database\Seeders;

use App\Models\Neighborhood;
use Illuminate\Database\Seeder;

class NeighborhoodSeeder extends Seeder
{
    public function run(): void
    {
        $neighborhoods = [
            'الرمال' => 'Al-Rimal',
            'الشجاعية' => 'Al-Shuja\'iyya',
            'الزيتون' => 'Al-Zaytoun',
            'النصر' => 'Al-Nasr',
            'تل الهوى' => 'Tel Al-Hawa',
            'الدرج' => 'Al-Daraj',
            'التفاح' => 'Al-Tuffah',
            'الصبرة' => 'Al-Sabra',
        ];

        foreach ($neighborhoods as $name => $nameEn) {
            Neighborhood::updateOrCreate(['name' => $name], ['name_en' => $nameEn]);
        }
    }
}
