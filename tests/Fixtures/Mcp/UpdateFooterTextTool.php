<?php

namespace Tests\Fixtures\Mcp;

use App\Mcp\Attributes\WritesData;
use App\Mcp\Tools\WriteTool;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('update-footer-text')]
#[Description('Update the site footer text. Requires the write permission and an idempotency key.')]
#[IsIdempotent]
#[WritesData(resource: 'setting', operation: 'update', permission: 'tests.write', idempotent: true)]
class UpdateFooterTextTool extends WriteTool
{
    protected function authorize(User $user): bool
    {
        return ! $user->isSuspended();
    }

    protected function perform(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'footer_text' => ['required', 'string', 'max:255'],
        ]);

        $setting = Setting::query()->firstOrFail();
        $before = $setting->footer_text;

        $setting = app(UpdateFooterText::class)->handle($setting, (string) $data['footer_text']);

        $this->auditContext = ['setting_id' => $setting->id];
        $this->auditResourceId = (string) $setting->id;
        $this->auditDiff = ['footer_text' => ['old' => $before, 'new' => $setting->footer_text]];

        return Response::structured([
            'id' => $setting->id,
            'footer_text' => $setting->footer_text,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'footer_text' => $schema->string()->required(),
            'idempotency_key' => $schema->string()->required(),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'footer_text' => $schema->string()->required(),
        ];
    }
}
