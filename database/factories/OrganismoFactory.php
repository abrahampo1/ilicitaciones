<?php

namespace Database\Factories;

use App\Models\Organismo;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganismoFactory extends Factory
{
    protected $model = Organismo::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company(),
            'identificador' => $this->faker->unique()->numerify('ORG#######'),
            'provincia' => $this->faker->randomElement(['Madrid', 'Sevilla', 'Valencia', 'Zaragoza']),
            'pais' => 'España',
            'total_importe' => 0,
            'total_licitaciones' => 0,
        ];
    }
}
