<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{

    /**
     * @brief Generates a standardized JSON response for the application.
     *
     * @details
     * This function constructs and returns a response in JSON format with a uniform structure,
     * including status, message, data, and timestamp. It also allows you to set the HTTP status code
     * and add custom headers.
     *
     * If the HTTP status code is 500, the error is reported and the message is replaced with a generic one
     * to avoid exposing internal system details.
     *
     * @param string $status Response status (e.g., ‘success’, ‘error’).
     * @param string $message Descriptive message for the response.
     * @param array $data Additional data to include in the response.
     * @param int $code HTTP status code (default 200).
     * @param array $headers Additional HTTP headers to include in the response.
     *
     * @return JsonResponse JSON response object ready to be returned to the client.
     */
    public static function response(
        string $status,
        string $message,
        array $data = [],
        int $code = 200,
        array $headers = []
    ): JsonResponse {
        if ($code == 500) {
            report($message);
            $message = 'internal error server';
        }

        $response = response()->json([
            'status'  => $status,
            'time'    => \Carbon\Carbon::now()->timestamp,
            'message' => $message,
            'data'    => $data,
        ], $code);

        if (!empty($headers)) {
            $response->headers->add($headers);
        }

        return $response;
    }
}
