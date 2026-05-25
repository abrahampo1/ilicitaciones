<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Enums\ArticleSection;
use App\Models\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = rtrim($this->faker->sentence(6), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 999999),
            'dek' => $this->faker->sentence(12),
            'body' => $this->faker->paragraphs(3, true),
            'body_format' => 'markdown',
            'section' => $this->faker->randomElement(ArticleSection::cases())->value,
            'status' => ArticleStatus::Draft->value,
            'published_at' => null,
            'author_name' => 'Redacción I-Licitaciones',
            'data' => null,
            'source_snapshot' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => ArticleStatus::Draft->value,
            'published_at' => null,
        ]);
    }

    public function review(): static
    {
        return $this->state(fn () => [
            'status' => ArticleStatus::Review->value,
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ArticleStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);
    }
}
