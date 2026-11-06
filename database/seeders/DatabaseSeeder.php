<?php

namespace Database\Seeders;

use App\Models\AssessmentPeriod;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            QuestionSeeder::class,
            AdminSeeder::class,
        ]);

        if (! AssessmentPeriod::where('is_active', true)->exists()) {
            AssessmentPeriod::create([
                'name' => config('assessment.seed.period_name'),
                'is_active' => true,
            ]);
        }
    }
}
