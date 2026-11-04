<?php

namespace Tests\Fixtures\Sam;

use App\Ai\Sam\SamProfile;

class FakeSamProfile implements SamProfile
{
    public function name(): string
    {
        return 'Fake Sam';
    }

    public function declaration(): string
    {
        return 'Hola, soy Fake Sam, un agente de prueba. Soy una IA, no una persona.';
    }

    public function instructions(): string
    {
        return 'Eres Fake Sam, el agente de prueba del kit.';
    }
}
