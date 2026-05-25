<?php

namespace Database\Factories;

use App\Models\Adjudicacion;
use App\Models\Empresa;
use App\Models\Licitacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdjudicacionFactory extends Factory
{
    protected $model = Adjudicacion::class;

    public function definition(): array
    {
        return [
            'licitacion_id' => Licitacion::factory(),
            'empresa_id' => Empresa::factory(),
            'importe' => $this->faker->randomFloat(2, 1000, 5000000),
            'importe_final' => $this->faker->randomFloat(2, 1000, 5000000),
            'fecha_adjudicacion' => $this->faker->dateTimeBetween('-5 years', 'now'),
        ];
    }
}
