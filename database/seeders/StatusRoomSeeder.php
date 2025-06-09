<?php

namespace Database\Seeders;

use App\Models\StatusRoom;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StatusRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            Str::random(10),
            Str::random(10),
            Str::random(10),
            Str::random(10),
        ];

        foreach ($statuses as $status) {
            StatusRoom::create([
                'name' => $status,
                'color' => $status,
            ]);
        }
    }
}
