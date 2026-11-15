<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HolidayService
{
    public function forYear(int $year, string $country): array
    {
        $country = strtoupper($country);

        return Cache::remember("holidays:{$country}:{$year}", now()->addDay(), function () use ($year, $country) {
            $response = Http::baseUrl(config('services.nager.base_url'))
                ->acceptJson()
                ->timeout(5)
                ->retry(2, 200, throw: false)
                ->get("/PublicHolidays/{$year}/{$country}");

            $response->throw();

            return collect($response->json())
                ->map(fn (array $holiday) => [
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                    'local_name' => $holiday['localName'],
                ])
                ->all();
        });
    }
}