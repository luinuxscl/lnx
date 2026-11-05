<?php

namespace App\Ai\Sam;

use App\Ai\LnxAgent;

/**
 * Agent that writes the model narrative of a brief.
 *
 * It has no tools and no conversation: it only turns the deterministic
 * signals into a short, grounded summary. The runtime verifies the output
 * before showing it; on failure the deterministic fallback stays.
 */
class SamBriefNarrativeAgent extends LnxAgent
{
    public function instructions(): string
    {
        $locale = app()->getLocale();

        return <<<PROMPT
        Eres Sam, el agente de la plataforma. Escribe un resumen breve del registro en foco.
        Reglas:
        - 2 a 4 frases, máximo 60 palabras.
        - Usa solo las señales listadas. No inventes datos, causas ni consecuencias.
        - No menciones nombres técnicos de señales.
        - Ordena por gravedad: primero lo que más riesgo o fricción causa.
        - No prometas acciones ni digas que ya hiciste algo.
        - No repitas el nombre del registro más de una vez.
        - Responde en {$locale}.
        Si no hay señales, di que no ves puntos de atención.
        PROMPT;
    }

    public function maxTokens(): int
    {
        return max(1, (int) config('lnx_sam.brief_max_tokens', 300));
    }

    public function timeout(): int
    {
        return max(1, (int) config('lnx_sam.brief_timeout', 4));
    }
}
