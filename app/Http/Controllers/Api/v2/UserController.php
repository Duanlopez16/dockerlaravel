<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

/**
 * UserController
 */
class UserController extends Controller
{
    /**
     * @brief Retrieves information about the authenticated user based on their token.
     *
     * @details
     * This function uses the user authenticated in the request to look up
     * their information in the database using the UserRepository.
     * 
     * If the user is found, their information is returned.
     * If not found, a message is returned indicating that the user does not exist.
     * In case of an error during the process, the exception is caught and an
     * error message with HTTP status code 500 is returned.
     *
     * Finally, the response is constructed using a standardized format
     * via the ApiResponse class.
     *
     * @param Request $request The HTTP request object containing the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the status, message, data, and HTTP code.
     */
    public function getUserToken(Request $request): \Illuminate\Http\JsonResponse
    {
        $response = [
            'status' => 'success',
            'statusCode' => 200,
            'message' => 'success',
            'data' => []
        ];
        try {
            $getUser = \App\Repositories\UserRepository::getUser(['email' => $request->user()->email], ['id']);
            if ($getUser['status'] && !empty($getUser['data'])) {
                $response['data'] = $getUser['data']->toArray();
            } else {
                $response['message'] = $getUser['status'] ? 'Token user not found' : 'Error';
                $response['statusCode'] = $getUser['status'] ? 404 : 500;
            }
        } catch (\Throwable $ex) {
            $response['status'] = 'Error';
            $response['statusCode'] = 500;
            $response['message'] = $ex->getMessage();
        }
        return ApiResponse::response($response['status'], $response['message'], (array)$response['data'], $response['statusCode']);
    }
}
