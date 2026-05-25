<?php

namespace Database\Factories;

use App\Models\Licitacion;
use App\Models\Organismo;
use Illuminate\Database\Eloquent\Factories\Factory;

class LicitacionFactory extends Factory
{
    protected $model = Licitacion::class;

    public function definition(): array
    {
        return [
            'titulo' => $this->faker->sentence(),
            'descripcion' => $this->faker->paragraph(),
            'identificador' => $this->faker->unique()->numerify('LIC-########'),
            'estado' => $this->faker->randomElement(['PUB', 'ADJ', 'RES']),
            'importe_total' => $this->faker->randomFloat(2, 1000, 5000000),
            'importe_final' => $this->faker->randomFloat(2, 1000, 5000000),
            'fecha_actualizacion' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'organismo_id' => Organismo::factory(),
            'categoria_id' => null,
            'datos_raiz' => null,
        ];
    }
}
