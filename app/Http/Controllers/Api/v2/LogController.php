<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\ApiResponse;
use App\Services\LogReaderService;
use App\Http\Requests\LogsFilterRequest;
use Carbon\Carbon;

class LogController extends Controller
{
    /**
     * @brief Retrieves filtered and paginated log entries.
     *
     * @details
     * This method processes an HTTP request to retrieve
     * system log entries using filters previously validated
     * via `LogsFilterRequest`.
     *
     * The main workflow includes:
     * - Retrieving validated parameters.
     * - Querying logs using `LogReaderService`.
     * - Constructing a structured response.
     * - Centralized error and exception handling.
     *
     * If the query is successful, it returns the found records
     * along with the corresponding pagination information.
     *
     * In the event of a controlled error or unexpected exception,
     * it returns a standardized response with the appropriate HTTP code.
     *
     * @param LogsFilterRequest $request Validated request containing
     *                                   the search filters:
     *                                   - level
     *                                   - date
     *                                   - cursor
     *                                   - limit
     *
     * @return mixed
     * Returns a structured HTTP response using
     * `ApiResponse::response`.
     *
     * @note
     * If no date is provided, the current system date is
     * automatically used.
     *
     * @warning
     * This method depends on the correct implementation
     * of `LogReaderService::getLogs`.
     */
    public function getLogs(LogsFilterRequest $request)
    {
        $responseLog = [
            'status' => 'success',
            'statusCode' => 200,
            'message' => 'success',
            'data' => []
        ];

        // Retrieves only the validated data from the request
        $params = $request->validated();

        try {

            /**
             * Queries the logs using the dedicated service.
             *
             * Parameters used:
             * - Log levels
             * - Date
             * - Pagination cursor
             * - Result limit
             */
            $getLogs = LogReaderService::getLogs(
                $params['level'],
                $params['date'] ?? Carbon::now()->format('Y-m-d'),
                $params['cursor'] ?? null,
                (int)$params['limit']
            );

            if ($getLogs['status']) {
                $responseLog['data'] = $getLogs['data'];
            } else {
                $responseLog['status'] = 'error';
                $responseLog['statusCode'] = $getLogs['statusCode'];
                $responseLog['message'] = $getLogs['message'];
            }
        } catch (\Throwable $th) {
            $responseLog['status'] = 'error';
            $responseLog['statusCode'] = 500;
            $responseLog['message'] = $th->getMessage();
        }

        return ApiResponse::response(
            $responseLog['status'],
            $responseLog['message'],
            (array)$responseLog['data'],
            $responseLog['statusCode']
        );;
    }
}
