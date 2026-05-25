<?php

namespace App\Http\Requests;

use App\Models\Enums\ArticleSection;
use App\Models\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    /** Etiquetas HTML permitidas cuando el cuerpo se guarda como html. */
    private const HTML_PERMITIDO = '<p><br><strong><em><ul><ol><li><h2><h3><h4><blockquote><a><table><thead><tbody><tr><th><td><hr>';

    public function authorize(): bool
    {
        return $this->user()?->can('manage-articles') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'dek' => ['nullable', 'string', 'max:300'],
            'body' => ['nullable', 'string'],
            'body_format' => ['required', Rule::in(['markdown', 'html'])],
            'section' => ['required', Rule::enum(ArticleSection::class)],
            'status' => ['required', Rule::enum(ArticleStatus::class)],
            'provincia' => ['nullable', 'string', 'max:120'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'data' => ['nullable', 'string'], // JSON crudo del editor
            // Enlaces a entidades: cadenas de IDs separadas por coma.
            'empresas' => ['nullable', 'string'],
            'organismos' => ['nullable', 'string'],
            'licitaciones' => ['nullable', 'string'],
            'categorias' => ['nullable', 'string'],
        ];
    }

    /** Cuerpo listo para guardar: si es html, saneado a una allow-list. */
    public function bodyForStorage(): ?string
    {
        $body = $this->input('body');

        if ($body === null) {
            return null;
        }

        if ($this->input('body_format') === 'html') {
            return strip_tags($body, self::HTML_PERMITIDO);
        }

        return $body;
    }

    /** Decodifica el campo data (JSON) si es válido, si no null. */
    public function dataForStorage(): ?array
    {
        $raw = $this->input('data');

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
    }

    /** @return array<int> IDs de una cadena "1, 2,3". */
    public function entityIds(string $campo): array
    {
        $raw = (string) $this->input($campo, '');

        return collect(explode(',', $raw))
            ->map(fn ($v) => (int) trim($v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
