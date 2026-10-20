<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::OpenRouter)]
#[Model('nvidia/nemotron-3-super-120b-a12b:free')]
class BusinessProfileAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TEXT'
        You extract a local business profile from the page text you are given.
        Use only details that are actually in that text. Leave a field empty when it is not there.
        Do not invent prices, hours, or services.
        Services should be short names a customer would ask for.
        TEXT;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'phone' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'city' => $schema->string()->required(),
            'region' => $schema->string()->required(),
            'services' => $schema->array()->items($schema->string())->required(),
            'faqs' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema) => [
                    'question' => $schema->string()->required(),
                    'answer' => $schema->string()->required(),
                ])
            )->required(),
            'facts' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema) => [
                    'category' => $schema->string()->enum(['pricing', 'booking', 'hours', 'location', 'other'])->required(),
                    'label' => $schema->string()->required(),
                    'value' => $schema->string()->required(),
                    'confidence' => $schema->string()->enum(['low', 'medium', 'high'])->required(),
                ])
            )->required(),
        ];
    }
}
