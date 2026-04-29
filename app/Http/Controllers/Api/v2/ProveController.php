<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Log;


class ProveController extends Controller
{

    /**
     * Liveness probe endpoint.
     *
     * Commonly used in environments such as Kubernetes or load balancers
     * to verify that the application is running correctly.
     *
     * It does NOT validate external dependencies (DB, APIs, etc.), it only confirms that
     * the service is active.
     *
     * @param Request $request Incoming HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response with service status
     */
    public function livenessProbe(Request $request): \Illuminate\Http\JsonResponse
    {
        $version = config('app.version');
        $description = config('app.name');
        $environment = config('app.env');

        return response()->json([
            'greeting' => "Hello from {$description} - {$environment}",
            'name' => 'Liveness Probe',
            'version' => "v.{$version}",
            'date' => Carbon::now()->toDateTimeString(),
        ], Response::HTTP_OK)
            ->header('Custom-Header', 'liveness probe');
    }

    /**
     * Startup probe endpoint.
     *
     * Primarily used in environments such as Kubernetes to determine
     * whether the application has completed its initialization process.
     *
     * Unlike the liveness check:
     * - This endpoint is used ONLY during startup
     * - It allows more time before the app is considered “failed”
     *
     * @param Request $request Incoming HTTP request
     * @return \Illuminate\Http\Response Standardized response
     */
    public function startup(Request $request): \Illuminate\Http\JsonResponse
    {
        $version = config('app.version');
        $description = config('app.name');
        $environment = config('app.env');

        return ApiResponse::response(
            'success',
            'startup probe',
            [
                'greeting' => "Hello from {$description} - {$environment}",
                'name' => 'startup probe',
                'version' => "v.{$version}",
                // 'data' => app(CommonService::class)->getEnvironmentVariables(),
                'date' => Carbon::now()->toIso8601String(),
            ],
            200,
            [
                'Custom-Header' => 'startup probe'
            ]
        );
    }

    /**
     * Runs the service readiness probe.
     *
     * This method evaluates the availability status of the application's
     * main services (e.g., casper, subscriptions, piano)
     * and determines an overall system status.
     *
     * General workflow:
     * - Initializes the services with a UNKNOWN status.
     * - (Optional) Runs individual validations for each service.
     * - Consolidates the results to determine the overall status:
     *     - HEALTHY: all services are available or have no critical failures.
     *     - DEGRADED: at least one service is degraded, but none are down.
     *     - UNHEALTHY: at least one service is down.
     *
     * Response:
     * - Returns a standardized structure via ApiResponse::response
     *   which includes:
     *     - Global system status.
     *     - Details of primary services.
     *     - Secondary services (if applicable).
     *
     * HTTP status code:
     * - 200 OK → HEALTHY or DEGRADED
     * - 500 INTERNAL SERVER ERROR → UNHEALTHY
     *
     * @return \Illuminate\Http\Response
     */
    public function readiness(): \Illuminate\Http\JsonResponse
    {
        $casper = [
            'name' => 'casper',
            'status' => 'UNKNOWN',
            'detail' => 'Error readiness probe',
        ];

        $subscriptions = [
            'name' => 'subscriptions',
            'status' => 'UNKNOWN',
            'detail' => 'Error readiness probe',
        ];

        $piano = [
            'name' => 'piano',
            'status' => 'UNKNOWN',
            'detail' => 'Something is wrong with service readinessprobe',
        ];

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $status = 'UNHEALTHY';

        // 🔹 CASPER
        // try {
        //     $casperService = app()->make('App\Repositories\UserRepository')
        //         ->getUser(['user_types_id' => 2], ['id']);

        //     if ($casperService) {
        //         $casper = [
        //             'name' => 'casper',
        //             'status' => 'HEALTHY',
        //             'detail' => '1 item found',
        //         ];
        //     }
        // } catch (\Throwable $e) {
        //     $casper = [
        //         'name' => 'casper',
        //         'status' => 'UNHEALTHY',
        //         'detail' => 'error: ' . $e->getMessage(),
        //     ];
        // }

        // 🔹 SUBSCRIPTIONS
        // try {
        //     $subscriptionsResponse = app()->make('App\Repositories\PinesRedimidosRepository')
        //         ->getRedeemedPin(
        //             ['pin' => ['like' => '2UNPgFzPEQkJ@TME9KPGOFE8G']],
        //             ['acceso']
        //         );

        //     if ($subscriptionsResponse) {
        //         $subscriptions = [
        //             'name' => 'subscriptions',
        //             'status' => 'HEALTHY',
        //             'detail' => '1 item found',
        //         ];
        //     } else {
        //         $subscriptions = [
        //             'name' => 'subscriptions',
        //             'status' => 'DEGRADED',
        //             'detail' => 'tries to connect but it does not matter',
        //         ];
        //     }
        // } catch (\Throwable $e) {
        //     $subscriptions = [
        //         'name' => 'subscriptions',
        //         'status' => 'UNKNOWN',
        //         'detail' => 'error: ' . $e->getMessage(),
        //     ];
        // }

        // 🔹 PIANO
        // try {
        //     $pianoService = app()->make('App\Services\PianoService');
        //     $response = $pianoService->getAppFeatures(config('services.piano.aid'));

        //     if (!empty($response['app_features'])) {
        //         $piano = [
        //             'name' => 'piano',
        //             'status' => 'HEALTHY',
        //             'detail' => '1 item found',
        //         ];
        //     }
        // } catch (\Throwable $e) {
        //     Log::error('piano readiness error', ['error' => $e->getMessage()]);

        //     $piano = [
        //         'name' => 'piano',
        //         'status' => 'UNHEALTHY',
        //         'detail' => 'error: ' . $e->getMessage(),
        //     ];
        // }

        // 🔹 Evaluación global
        $services = [$casper, $subscriptions, $piano];

        $hasDegraded = collect($services)->contains(fn($s) => $s['status'] === 'DEGRADED');
        $hasUnhealthy = collect($services)->contains(fn($s) => $s['status'] === 'UNHEALTHY');

        if ($hasDegraded && !$hasUnhealthy) {
            $statusCode = Response::HTTP_OK;
            $status = 'DEGRADED';
        } elseif ($hasUnhealthy) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $status = 'UNHEALTHY';
        } else {
            $statusCode = Response::HTTP_OK;
            $status = 'HEALTHY';
        }

        return ApiResponse::response(
            $status,
            'readiness probe',
            [
                'main_services' => $services,
                'secondary_services' => [],
            ],
            $statusCode
        );
    }
}
