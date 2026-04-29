<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Curl
 */
class Curl
{

    /**
     * @brief Sends an HTTP GET request to a specific URL.
     *
     * @details
     * This method executes a GET request using the HTTP client and returns
     * a standardized response via the `validateRequest` method.
     * 
     * Allows you to send optional parameters that will be included in the query string
     * of the request. In case of an error during execution (e.g., network issues
     * or HTTP client exceptions), the exception is caught and a response with an error status is returned.
     *
     * @param string $url The destination URL to which the GET request will be made.
     * @param array $params Optional parameters to be sent as a query string
     *                      in the HTTP request.
     *
     * @return array Associative array containing the standardized response, which includes:
     *               - status (bool): indicates whether the operation was successful.
     *               - message (string): descriptive message of the result or error.
     *               - data (array): data returned by the request.
     *
     * @note
     * The method relies on `validateRequest` to normalize the HTTP client response.
     *
     * @warning
     * In the event of an exception, the error is not thrown but is encapsulated in the response,
     * so the consumer must validate the `status` field.
     */
    public static function get(string $url, array $params = []): array
    {
        try {
            // Executes the HTTP GET request with the provided parameters
            $response = Http::get($url, $params);

            // Validate and normalize the response before returning it
            return self::validateRequest($response);
        } catch (\Throwable $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * @brief Sends an HTTP POST request to a specific URL.
     *
     * @details
     * This method executes an HTTP POST request using a configurable HTTP client.
     * It allows data to be sent in different formats depending on the specified content type.
     * 
     * By default, data is sent in JSON format, but it also supports
     * `application/x-www-form-urlencoded` when compatibility with traditional forms is required.
     * 
     * The response is processed using the `validateRequest` method to maintain
     * a standardized structure. In case of an error, the exception is caught and a
     * controlled response indicating the failure is returned.
     *
     * @param string $url Destination URL to which the POST request will be made.
     * @param array $data Data to be sent in the request body.
     * @param string $contentType Request content type. Can be:
     *                            - ‘application/json’ (default)
     *                            - ‘application/x-www-form-urlencoded’
     *
     * @return array An associative array containing the parsed response, including:
     *               - status (bool): Indicates whether the operation was successful.
     *               - message (string): A descriptive message about the result or error.
     *               - data (array): data returned by the request.
     *
     * @note
     * The method uses `validateRequest` to process and normalize the HTTP client response.
     *
     * @warning
     * If an exception occurs, it is not propagated; instead, a response with `status = false` is returned.
     * It is the consumer’s responsibility to validate the response before using the data.
     */
    public static function post(string $url, array $data, string $contentType = 'application/json'): array
    {
        try {
            $client = Http::withOptions([]);

            if ($contentType === 'application/x-www-form-urlencoded') {
                $client = $client->asForm();
            }

            $response = $client->post($url, $data);

            return self::validateRequest($response);
        } catch (\Throwable $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * @brief Validates and normalizes the response to an HTTP request.
     *
     * @details
     * This method processes the response received from an HTTP client and transforms
     * it into a standardized structure for use within the application.
     * 
     * Unlike more restrictive implementations, this method considers
     * all responses with HTTP status codes in the 2xx range (200–299) to be successful,
     * allowing for greater flexibility with different types of valid responses
     * (for example: 201 Created, 204 No Content).
     * 
     * The flow includes:
     * - Retrieving the HTTP status code.
     * - Success validation for any 2xx code.
     * - Decoding the response body (JSON).
     * - Constructing a uniform response with additional metadata.
     * 
     * In case of an error (codes outside the 2xx range), the HTTP code is returned without
     * processing the body. If an exception occurs, it is caught and reflected in the message.
     *
     * @param mixed $request HTTP response object that must implement:
     *                       - getStatusCode()
     *                       - getBody()
     *                       - headers()
     *
     * @return array Associative array with the following structure:
     *               - status (bool): Indicates whether the response was successful (HTTP 2xx).
     *               - message (string): Result message (‘success’ or ‘error’).
     *               - data (array): decoded data from the JSON body.
     *               - data_response_curl (array): additional response information (only if successful).
     *               - status_response (int): HTTP status code in case of error.
     *
     * @warning
     * - If the body does not contain valid JSON, `data` will be an empty array.
     * - For responses with no content (e.g., 204), `data` will be an empty array.
     * - Exceptions are not propagated; they are encapsulated in the `message` field.
     */
    public static function validateRequest($request): array
    {
        $result = [
            'status' => false,
            'message' => 'error',
            'data' => [],
        ];

        try {
            $statusCode = (int) $request->getStatusCode();

            // Treats any response in the 2xx range as successful
            if ($statusCode >= 200 && $statusCode < 300) {

                // Retrieves the response body
                $body = (string) $request->getBody();

                // Attempts to decode the JSON into an associative array
                $decoded = json_decode($body, true);

                // Build a successful response
                $result['status'] = true;
                $result['message'] = 'success';

                // Assign data only if the JSON is valid
                $result['data'] = is_array($decoded) ? $decoded : [];

                // Includes HTTP response metadata
                $result['data_response_curl'] = [
                    'status_response' => $statusCode,
                    'headers' => $request->headers(),
                ];
            } else {
                // For unsuccessful responses, the HTTP status code is returned
                $result['status_response'] = $statusCode;
            }
        } catch (\Throwable $e) {
            // Exception handling: catches the error and assigns it to the message
            $result['message'] = $e->getMessage();
        }

        return $result;
    }
}
