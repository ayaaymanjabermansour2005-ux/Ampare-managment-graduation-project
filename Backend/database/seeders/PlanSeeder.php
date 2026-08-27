<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::firstOrCreate(['code' => 'basic'], [
            'name' => 'أساسية',
            'max_generators' => 1,
            'price_monthly' => 0,
            'is_active' => true,
        ]);
        Plan::firstOrCreate(['code' => 'pro'], [
            'name' => 'احترافية',
            'max_generators' => 5,
            'price_monthly' => 50,
            'is_active' => true,
        ]);
        Plan::firstOrCreate(['code' => 'enterprise'], [
            'name' => 'مؤسسية',
            'max_generators' => null,
            'price_monthly' => 150,
            'is_active' => true,
        ]);
    }
}
