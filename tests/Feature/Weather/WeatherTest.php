<?php

namespace Tests\Feature\Weather;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Services\WeatherService;

class WeatherTest extends TestCase
{

    // Test para verificar el servicio que la API de clima devuelve datos correctamente
    public function test_get_weather_by_city_returns_data()
    {
        $city = 'Bogota';
        $cacheKey = 'weather_' . strtolower($city);

        Cache::shouldReceive('remember')
            ->once()
            ->with($cacheKey, 10, \Closure::class)
            ->andReturnUsing(function ($key, $minutes, $callback) {
                return $callback();
            });

        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => ['name' => 'Bogota'],
                'current' => ['temp_c' => 18],
            ], 200)
        ]);

        $service = new WeatherService();
        $response = $service->getWeatherByCity($city);

        $this->assertEquals('Bogota', $response['location']['name']);
        $this->assertEquals(18, $response['current']['temp_c']);
    }
}
