<?php

namespace App\Services\LLM;

/**
 * Contrato común de los redactores LLM (Anthropic / OpenAI). Reciben el system prompt
 * y el payload factual y devuelven el borrador estructurado.
 */
interface ArticleDrafter
{
    /**
     * @param  array<string,mixed>  $payload  datos exactos del candidato
     * @return array{title:string,dek:string,body:string,suggested_section:string,confidence:float}
     */
    public function redactar(string $systemPrompt, array $payload): array;
}
