<?php

namespace Database\Seeders;

use App\Models\Webhook;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WebhookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Webhook::create([
            'name' => 'Sample Webhook',
            'description' => 'This is a sample webhook for testing purposes.',
            'active' => true
        ]);
    }
}
