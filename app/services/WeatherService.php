<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    protected $apiKey;
    protected $cacheMinutes = 10;

    public function __construct()
    {
        $this->apiKey = config('weather.api_key');
    }

    public function getWeatherByCity(string $city): array
    {
        $cacheKey = 'weather_' . strtolower($city);

        return Cache::remember($cacheKey, $this->cacheMinutes, function () use ($city) {
            $response = Http::get("https://api.weatherapi.com/v1/current.json", [
                'key' => $this->apiKey,
                'q' => $city
            ]);

            if ($response->failed()) {
                throw new \Exception('Error al obtener datos del API');
            }

            return $response->json();
        });
    }
}
