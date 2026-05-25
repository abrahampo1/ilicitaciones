<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company(),
            'identificador' => $this->faker->unique()->numerify('B#######'),
            'total_importe' => 0,
            'total_adjudicaciones' => 0,
        ];
    }
}
