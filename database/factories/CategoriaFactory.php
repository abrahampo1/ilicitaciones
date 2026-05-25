<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->words(3, true),
            'xml_id' => $this->faker->unique()->numerify('########'),
        ];
    }
}
