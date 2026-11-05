<?php

namespace App\Ai\Sam;

use Illuminate\Support\Str;

/**
 * Verifies that a model narrative is grounded in the deterministic signals.
 *
 * The verifier never rewrites the text: it accepts it or returns a reason so
 * the runtime can log the rejection and keep the deterministic fallback.
 */
final class SamBriefNarrativeVerifier
{
    /**
     * Phrases that claim the agent already performed a write.
     *
     * @var list<string>
     */
    private const FORBIDDEN_PHRASES = [
        'he actualizado',
        'he creado',
        'he guardado',
        'he borrado',
        'he eliminado',
        'he enviado',
        'he archivado',
        'he restaurado',
        'ya esta hecho',
        'i have updated',
        'i have created',
        'i have saved',
        'i have deleted',
        'i have sent',
        'i have archived',
        'i have restored',
        'already done',
    ];

    /**
     * Domain fields that are not part of the declared signals.
     *
     * @var list<string>
     */
    private const FORBIDDEN_FIELDS = [
        'facturacion',
        'pipeline',
        'oportunidad',
        'oportunidades',
        'cotizacion',
        'cotizaciones',
        'automatizacion',
        'automatizaciones',
        'ingreso',
        'ingresos',
    ];

    /**
     * Stable signal names that must never reach the interface.
     *
     * @var list<string>
     */
    private const TECHNICAL_SIGNALS = [
        'missing_company',
        'missing_owner',
        'missing_channel',
        'duplicate_email',
        'duplicate_name',
        'duplicate_website',
        'archived_company',
        'archived_with_active_contacts',
    ];

    /**
     * Returns a rejection reason, or null when the narrative is grounded.
     */
    public function verify(SamBrief $brief, string $narrative): ?string
    {
        $narrative = trim($narrative);

        if ($narrative === '') {
            return 'empty';
        }

        $normalized = mb_strtolower(Str::ascii($narrative));

        foreach (self::FORBIDDEN_PHRASES as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return 'promise';
            }
        }

        foreach (self::TECHNICAL_SIGNALS as $signal) {
            if (str_contains($normalized, $signal)) {
                return 'technical_signal';
            }
        }

        foreach (self::FORBIDDEN_FIELDS as $field) {
            if (str_contains($normalized, $field)) {
                return 'out_of_scope_field';
            }
        }

        $allowed = $this->allowedNumbers($brief);

        preg_match_all('/\d+/', $narrative, $matches);

        foreach ($matches[0] as $number) {
            if (! in_array((int) $number, $allowed, true)) {
                return 'number_not_grounded';
            }
        }

        return null;
    }

    /**
     * Numbers the narrative may use: signal values, signal count and the label.
     *
     * @return list<int>
     */
    private function allowedNumbers(SamBrief $brief): array
    {
        $allowed = [count($brief->signals)];

        foreach ($brief->signals as $signal) {
            if (is_int($signal->value)) {
                $allowed[] = $signal->value;
            }
        }

        if (is_string($brief->label)) {
            preg_match_all('/\d+/', $brief->label, $matches);

            foreach ($matches[0] as $number) {
                $allowed[] = (int) $number;
            }
        }

        return array_values(array_unique($allowed));
    }
}
