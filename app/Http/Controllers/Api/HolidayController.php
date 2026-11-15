<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HolidayRequest;
use App\Services\HolidayService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class HolidayController extends Controller
{
    public function index(HolidayRequest $request, HolidayService $holidays): JsonResponse
    {
        $data = $request->validated();
        $year = (int) ($data['year'] ?? now()->year);

        try {
            return response()->json([
                'data' => $holidays->forYear($year, $data['country']),
            ]);
        } catch (RequestException $e) {
            if ($e->response->clientError()) {
                return response()->json([
                    'message' => 'Holidays are not available for the given country or year.',
                ], 404);
            }

            report($e);

            return response()->json(['message' => 'Holiday service is unavailable.'], 502);
        } catch (ConnectionException $e) {
            report($e);

            return response()->json(['message' => 'Holiday service is unavailable.'], 502);
        }
    }
}
