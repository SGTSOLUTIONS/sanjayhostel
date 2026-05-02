<?php

namespace Database\Seeders;

use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Method 1: Create specific room types manually
        $roomTypes = [
            // AC Rooms
            [
                'name' => 'Single AC Room',
                'is_ac' => true,
                'sharing' => 1,
                'cot_type' => 'normal',
                'rent_with_food' => 8500.00,
                'rent_without_food' => 6500.00,
                'created_by' => 1,
            ],
            [
                'name' => 'Double AC Room',
                'is_ac' => true,
                'sharing' => 2,
                'cot_type' => 'normal',
                'rent_with_food' => 7000.00,
                'rent_without_food' => 5500.00,
                'created_by' => 1,
            ],
            [
                'name' => 'Triple AC Room',
                'is_ac' => true,
                'sharing' => 3,
                'cot_type' => 'bunker',
                'rent_with_food' => 6500.00,
                'rent_without_food' => 5000.00,
                'created_by' => 1,
            ],
            [
                'name' => '4-Seater AC Dormitory',
                'is_ac' => true,
                'sharing' => 4,
                'cot_type' => 'bunker',
                'rent_with_food' => 6000.00,
                'rent_without_food' => 4800.00,
                'created_by' => 1,
            ],
            [
                'name' => '6-Seater AC Dormitory',
                'is_ac' => true,
                'sharing' => 6,
                'cot_type' => 'bunker',
                'rent_with_food' => 5500.00,
                'rent_without_food' => 4200.00,
                'created_by' => 1,
            ],

            // Non-AC Rooms
            [
                'name' => 'Single Non-AC Room',
                'is_ac' => false,
                'sharing' => 1,
                'cot_type' => 'normal',
                'rent_with_food' => 6500.00,
                'rent_without_food' => 4500.00,
                'created_by' => 1,
            ],
            [
                'name' => 'Double Non-AC Room',
                'is_ac' => false,
                'sharing' => 2,
                'cot_type' => 'normal',
                'rent_with_food' => 5500.00,
                'rent_without_food' => 4000.00,
                'created_by' => 1,
            ],
            [
                'name' => 'Triple Non-AC Room',
                'is_ac' => false,
                'sharing' => 3,
                'cot_type' => 'bunker',
                'rent_with_food' => 5000.00,
                'rent_without_food' => 3800.00,
                'created_by' => 1,
            ],
            [
                'name' => '4-Seater Non-AC Dormitory',
                'is_ac' => false,
                'sharing' => 4,
                'cot_type' => 'bunker',
                'rent_with_food' => 4800.00,
                'rent_without_food' => 3600.00,
                'created_by' => 1,
            ],
            [
                'name' => '8-Seater Non-AC Dormitory',
                'is_ac' => false,
                'sharing' => 8,
                'cot_type' => 'bunker',
                'rent_with_food' => 4500.00,
                'rent_without_food' => 3500.00,
                'created_by' => 1,
            ],
        ];

        // Insert all room types
        foreach ($roomTypes as $roomType) {
            RoomType::create($roomType);
        }

        // Method 2: Using factory to generate additional random room types
        // This will create 10 more random room types
        RoomType::factory()
            ->count(10)
            ->create(['created_by' => 1]);

        // Method 3: Using factory states for specific types
        // Uncomment if you want specific types with factory
        /*
        RoomType::factory()
            ->singleAc()
            ->create(['created_by' => 1]);

        RoomType::factory()
            ->doubleNonAc()
            ->create(['created_by' => 1]);

        RoomType::factory()
            ->tripleAc()
            ->create(['created_by' => 1]);

        RoomType::factory()
            ->dormitoryAc()
            ->create(['created_by' => 1]);
        */
    }
}
