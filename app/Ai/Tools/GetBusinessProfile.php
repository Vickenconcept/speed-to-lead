<?php

namespace App\Ai\Tools;

use App\Models\Business;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetBusinessProfile implements Tool
{
    public function __construct(public Business $business) {}

    public function name(): string
    {
        return 'get_business_profile';
    }

    public function description(): string
    {
        return 'Read the saved business profile, including services, hours, and sourced facts.';
    }

    public function handle(Request $request): string
    {
        return $this->business->profileForAgent();
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
