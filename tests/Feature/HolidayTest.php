<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HolidayTest extends TestCase
{
    use RefreshDatabase;

    private function fakeHolidays(): void
    {
        Http::fake([
            'date.nager.at/*' => Http::response([
                [
                    'date' => '2026-01-01',
                    'localName' => 'Neujahr',
                    'name' => "New Year's Day",
                    'countryCode' => 'AT',
                    'global' => true,
                ],
            ], 200),
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/holidays?country=AT')->assertUnauthorized();
    }

    public function test_country_is_required(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/holidays')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('country');
    }

    public function test_returns_holidays_from_external_api(): void
    {
        $this->fakeHolidays();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/holidays?country=AT&year=2026')
            ->assertOk()
            ->assertJsonPath('data.0.name', "New Year's Day")
            ->assertJsonPath('data.0.local_name', 'Neujahr');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/PublicHolidays/2026/AT'));
    }

    public function test_result_is_cached(): void
    {
        $this->fakeHolidays();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/holidays?country=AT&year=2026')->assertOk();
        $this->getJson('/api/holidays?country=AT&year=2026')->assertOk();

        Http::assertSentCount(1);
    }

    public function test_returns_502_when_external_api_fails(): void
    {
        Http::fake(['date.nager.at/*' => Http::response('error', 500)]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/holidays?country=AT&year=2026')->assertStatus(502);
    }

    public function test_returns_404_when_country_is_not_supported(): void
    {
        Http::fake(['date.nager.at/*' => Http::response('', 404)]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/holidays?country=ZZ&year=2026')->assertNotFound();
    }
}