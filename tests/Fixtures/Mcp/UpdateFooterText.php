<?php

namespace Tests\Fixtures\Mcp;

use App\Models\Setting;

/**
 * Test-only domain action backing the sample write tool. Proves that a write
 * tool delegates to a shared action instead of touching the model directly.
 */
class UpdateFooterText
{
    public function handle(Setting $setting, string $footerText): Setting
    {
        $setting->update(['footer_text' => $footerText]);

        return $setting->refresh();
    }
}
