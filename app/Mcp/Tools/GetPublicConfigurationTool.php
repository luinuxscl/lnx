<?php

namespace App\Mcp\Tools;

use App\Models\Setting;
use App\Models\User;
use App\Support\PublicConfiguration;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Name('get-public-configuration')]
#[Description('Read the non-sensitive public configuration of the application. Returns only an explicit whitelist of fields and never secrets or environment values.')]
class GetPublicConfigurationTool extends ReadOnlyTool
{
    protected function authorize(User $user): bool
    {
        return $user->can('viewAny', Setting::class);
    }

    protected function perform(Request $request): Response|ResponseFactory
    {
        return Response::structured(PublicConfiguration::all());
    }

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && $this->authorize($user);
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'application' => $schema->object([
                'name' => $schema->string()->required(),
                'version' => $schema->string()->required(),
            ])->required(),
            'localization' => $schema->object([
                'locale' => $schema->string()->required(),
                'fallback_locale' => $schema->string()->required(),
                'available_locales' => $schema->array()->items($schema->string())->required(),
                'timezone' => $schema->string()->required(),
            ])->required(),
            'site' => $schema->object([
                'title' => $schema->string()->nullable(),
                'tagline' => $schema->string()->nullable(),
                'meta_description' => $schema->string()->nullable(),
                'footer_text' => $schema->string()->nullable(),
                'registration_mode' => $schema->string()->required(),
                'maintenance_mode' => $schema->boolean()->required(),
            ])->required(),
        ];
    }
}
