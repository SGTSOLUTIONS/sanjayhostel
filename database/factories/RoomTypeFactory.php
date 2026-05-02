<?php

namespace Database\Factories;

use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomTypeFactory extends Factory
{
    protected $model = RoomType::class;

    public function definition()
    {
        $sharingOptions = [1, 2, 3, 4, 6, 8];
        $sharing = $this->faker->randomElement($sharingOptions);
        $is_ac = $this->faker->boolean();

        // Base rent calculation
        $baseRent = $sharing * 1000;
        $acSurcharge = $is_ac ? 2000 : 0;

        return [
            'name' => $this->generateRoomTypeName($sharing, $is_ac),
            'is_ac' => $is_ac,
            'sharing' => $sharing,
            'cot_type' => $this->faker->randomElement(['normal', 'bunker']),
            'rent_with_food' => $baseRent + $acSurcharge + 1500,
            'rent_without_food' => $baseRent + $acSurcharge,
            'created_by' => 1, // Default admin user, you can change this
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function generateRoomTypeName($sharing, $is_ac)
    {
        $sharingText = '';
        switch ($sharing) {
            case 1:
                $sharingText = 'Single';
                break;
            case 2:
                $sharingText = 'Double';
                break;
            case 3:
                $sharingText = 'Triple';
                break;
            case 4:
                $sharingText = 'Quad';
                break;
            case 6:
                $sharingText = '6-Seater';
                break;
            case 8:
                $sharingText = '8-Seater';
                break;
        }

        $acText = $is_ac ? 'AC' : 'Non-AC';

        return $sharingText . ' ' . $acText . ' Room';
    }

    // State modifiers for specific room types
    public function singleAc()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Single AC Room',
                'is_ac' => true,
                'sharing' => 1,
                'cot_type' => 'normal',
                'rent_with_food' => 8500,
                'rent_without_food' => 6500,
            ];
        });
    }

    public function singleNonAc()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Single Non-AC Room',
                'is_ac' => false,
                'sharing' => 1,
                'cot_type' => 'normal',
                'rent_with_food' => 6500,
                'rent_without_food' => 4500,
            ];
        });
    }

    public function doubleAc()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Double AC Room',
                'is_ac' => true,
                'sharing' => 2,
                'cot_type' => 'normal',
                'rent_with_food' => 7000,
                'rent_without_food' => 5500,
            ];
        });
    }

    public function doubleNonAc()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Double Non-AC Room',
                'is_ac' => false,
                'sharing' => 2,
                'cot_type' => 'normal',
                'rent_with_food' => 5500,
                'rent_without_food' => 4000,
            ];
        });
    }

    public function tripleAc()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Triple AC Room',
                'is_ac' => true,
                'sharing' => 3,
                'cot_type' => 'bunker',
                'rent_with_food' => 6500,
                'rent_without_food' => 5000,
            ];
        });
    }

    public function tripleNonAc()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Triple Non-AC Room',
                'is_ac' => false,
                'sharing' => 3,
                'cot_type' => 'bunker',
                'rent_with_food' => 5000,
                'rent_without_food' => 3800,
            ];
        });
    }

    public function dormitoryAc()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => '6-Seater AC Dormitory',
                'is_ac' => true,
                'sharing' => 6,
                'cot_type' => 'bunker',
                'rent_with_food' => 5500,
                'rent_without_food' => 4200,
            ];
        });
    }

    public function dormitoryNonAc()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => '8-Seater Non-AC Dormitory',
                'is_ac' => false,
                'sharing' => 8,
                'cot_type' => 'bunker',
                'rent_with_food' => 4500,
                'rent_without_food' => 3500,
            ];
        });
    }
}
