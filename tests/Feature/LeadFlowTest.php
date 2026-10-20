<?php

namespace Tests\Feature;

use App\Ai\Agents\LeadAgent;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_customer_sees_the_model_reply(): void
    {
        config(['ai.providers.openrouter.key' => 'test']);

        LeadAgent::fake(fn (string $prompt): array => str_contains($prompt, 'nnnn')
            ? $this->reply('We help people fund a build. What city is the project in?')
            : $this->reply('What can I help you with?', 'waiting', 'Project setup'));

        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();

        $this->postJson(route('hooks.website', $business->intake_token), [
            'name' => 'John Smith',
            'phone' => '+1 512 555 0199',
            'message' => 'I need help with a project.',
        ])->assertOk();

        $lead = $business->leads()->firstOrFail();

        $this->assertSame('Project setup', $lead->service_requested);
        $this->assertSame(
            'What can I help you with?',
            $lead->messages()->where('role', 'assistant')->value('body'),
        );

        $this->post(route('conversation.store', $lead->public_token), [
            'body' => 'nnnn',
        ])->assertRedirect();

        $lead->refresh();

        $this->assertSame(
            'We help people fund a build. What city is the project in?',
            $lead->messages()->where('role', 'assistant')->latest('id')->value('body'),
        );
        LeadAgent::assertPromptedTimes(2);
    }

    public function test_a_follow_up_is_sent_as_its_own_message(): void
    {
        config(['ai.providers.openrouter.key' => 'test']);

        LeadAgent::fake(fn (string $prompt): array => str_contains($prompt, 'uk')
            ? $this->reply('We do not cover that area.', 'closed')
            : $this->reply('Tell me about the job.'));

        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();

        $this->postJson(route('hooks.website', $business->intake_token), [
            'name' => 'Lacy',
            'message' => 'Hello',
        ])->assertOk();

        $lead = $business->leads()->firstOrFail();

        $this->post(route('conversation.store', $lead->public_token), [
            'body' => 'do you work in the uk',
        ])->assertRedirect();

        LeadAgent::assertPrompted(fn ($prompt): bool => $prompt->prompt === 'do you work in the uk');

        $this->assertSame(
            'We do not cover that area.',
            $lead->messages()->where('role', 'assistant')->latest('id')->value('body'),
        );
    }

    public function test_manual_onboarding_reaches_review(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('onboarding.manual'), [
                'name' => 'ABC Roofing',
                'phone' => '+1 512 555 0148',
                'website' => 'https://abcroofing.example',
                'description' => 'Roof repair and replacement.',
                'city' => 'Austin',
                'region' => 'Texas',
                'timezone' => 'America/Chicago',
                'opens' => '08:00',
                'closes' => '18:00',
                'services' => "Roof repair\nRoof replacement",
                'notes' => 'Same-day emergency leaks.',
            ])
            ->assertRedirect(route('onboarding.review'));

        $this->assertDatabaseHas('businesses', [
            'user_id' => $user->id,
            'name' => 'ABC Roofing',
            'city' => 'Austin',
        ]);

        $this->assertDatabaseHas('business_facts', [
            'label' => 'Roof repair',
            'source' => 'owner',
            'confidence' => 'high',
        ]);
    }

    public function test_google_onboarding_requires_a_google_link(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('onboarding.show'))
            ->post(route('onboarding.google'), ['url' => 'https://example.com'])
            ->assertRedirect(route('onboarding.show'))
            ->assertSessionHasErrors('url');
    }

    /**
     * @return array<string, string>
     */
    private function reply(string $reply, string $status = 'waiting', string $service = 'General request'): array
    {
        return [
            'reply' => $reply,
            'status' => $status,
            'service_requested' => $service,
            'location' => '',
            'intent' => 'New lead',
            'urgency' => 'low',
        ];
    }
}
