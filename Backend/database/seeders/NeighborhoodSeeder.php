<?php

namespace Database\Seeders;

use App\Models\Neighborhood;
use Illuminate\Database\Seeder;

class NeighborhoodSeeder extends Seeder
{
    public function run(): void
    {
        // LOCALIZATION-demo-data: name_en was a real, fillable, API-returned column
        // that the frontend already correctly preferred in English locale (see
        // RegisterView.vue's neighborhoodOptions) — but no seeder ever set it, so
        // English-locale users always saw the Arabic name anyway. Same root cause
        // class as the earlier Generator/GeneratorSchedule gaps.
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
