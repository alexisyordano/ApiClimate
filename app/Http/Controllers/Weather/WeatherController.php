<?php

namespace App\Http\Controllers\Weather;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WeatherData;
use Illuminate\Support\Facades\Auth;
use App\Models\SearchHistory;
use App\Models\FavoriteCity;
use App\Services\WeatherService;



class WeatherController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * @OA\Get(
     *     path="/api/weather",
     *     summary="Obtener el clima por ciudad",
     *     tags={"Clima"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         description="Nombre de la ciudad (opcional, por defecto 'El Vigia')",
     *         required=false,
     *         @OA\Schema(type="string", example="El Vigia")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Datos del clima obtenidos correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="city", type="string", example="El Vigia"),
     *             @OA\Property(property="localtime", type="string", example="2025-05-14 15:00"),
     *             @OA\Property(property="temperature_celsius", type="number", format="float", example=25.3),
     *             @OA\Property(property="condition", type="string", example="Soleado"),
     *             @OA\Property(property="humidity", type="integer", example=40),
     *             @OA\Property(property="wind_kph", type="number", format="float", example=15.2),
     *             @OA\Property(property="user_id", type="integer", nullable=true, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontraron datos para la ciudad especificada",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="No se encontraron datos para la ciudad especificada.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="No se pudo obtener el clima."),
     *             @OA\Property(property="message", type="string", example="Detalles del error")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {

        try {
            $userId = Auth::guard('api')->id();
            $city = $request->query('city', 'El Vigia');
            $response = $this->weatherService->getWeatherByCity($city);

            if (empty($response)) {
                return response()->json(['error' => 'No se encontraron datos para la ciudad especificada.'], 404);
            }

            // Guardar en el historial de búsquedas
            if ($userId) {
                SearchHistory::create([
                    'city' => $response['location']['name'],
                    'user_id' => $userId
                ]);
            }

            $this->saveWeatherData($response);

            return response()->json([
                'city' => $response['location']['name'],
                'localtime' => $response['location']['localtime'],
                'temperature_celsius' => $response['current']['temp_c'],
                'condition' => $response['current']['condition']['text'],
                'humidity' => $response['current']['humidity'],
                'wind_kph' => $response['current']['wind_kph'],
                'user_id' => $userId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo obtener el clima.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function saveWeatherData($data)
    {
        try {

            WeatherData::updateOrCreate(
                ['city' => $data['location']['name']],
                [
                    'localtime' => $data['location']['localtime'],
                    'temperature_celsius' => $data['current']['temp_c'],
                    'condition' => $data['current']['condition']['text'],
                    'humidity' => $data['current']['humidity'],
                    'wind_kph' => $data['current']['wind_kph'],
                ]
            );
        } catch (\Exception $e) {
            // Manejo de errores al guardar en la base de datos
            Log::error('Error al guardar los datos del clima: ' . $e->getMessage());
        }
    }


    /**
     * @OA\Post(
     *     path="/api/mark-favorite",
     *     summary="Marcar una ciudad como favorita",
     *     tags={"Favoritos"},
     *    security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"city"},
     *             @OA\Property(property="city", type="string", example="El Vigia")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ciudad marcada como favorita",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ciudad marcada como favorita.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error por ciudad no encontrada en historial o ya marcada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No puedes marcar como favorita una ciudad que no has buscado.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error interno del servidor.")
     *         )
     *     )
     * )
     */
    public function markAsFavorite(Request $request)
    {
        try {
            $userId = Auth::id();
            $city = $request->input('city');

            // Verificar que la ciudad exista en el historial de búsquedas del usuario
            $existsInHistory = SearchHistory::where('city', $city)
                ->where('user_id', $userId)
                ->exists();

            if (!$existsInHistory) {
                return response()->json(['message' => 'No puedes marcar como favorita una ciudad que no has buscado.'], 400);
            }

            // Verificar si ya está en favoritos
            $favorite = FavoriteCity::where('city', $city)
                ->where('user_id', $userId)
                ->first();

            if ($favorite) {
                return response()->json(['message' => 'Esta ciudad ya está en tus favoritos.'], 400);
            }

            // Guardar en favoritos
            FavoriteCity::create([
                'city' => $city,
                'user_id' => $userId
            ]);

            return response()->json(['message' => 'Ciudad marcada como favorita.']);
        } catch (\Exception $e) {
            Log::error('Error al marcar ciudad como favorita: ' . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor.'], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/search-history",
     *     summary="Historial de búsquedas del usuario",
     *     tags={"Historial"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista del historial",
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     ),
     *     @OA\Response(response=401, description="No autorizado")
     * )
     */
    public function getSearchHistory()
    {
        $userId = Auth::id();
        $history = SearchHistory::where('user_id', $userId)->orderBy('created_at', 'desc')->get();

        return response()->json($history);
    }

    /**
     * @OA\Get(
     *     path="/api/favorite-cities",
     *     summary="Obtener ciudades favoritas del usuario",
     *     tags={"Favoritos"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de ciudades favoritas",
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     ),
     *     @OA\Response(response=401, description="No autorizado")
     * )
     */
    public function getFavoriteCities()
    {
        $userId = Auth::id();
        $favorites = FavoriteCity::where('user_id', $userId)->get();

        return response()->json($favorites);
    }
}
