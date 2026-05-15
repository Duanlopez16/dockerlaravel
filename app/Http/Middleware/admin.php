<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Support\ApiResponse;


class admin
{

    /**
     * @brief Handles permission and access validation for the authenticated user.
     *
     * @details
     * This middleware validates whether the authenticated user has administrator permissions
     * to allow access to the next layer of the application.
     *
     * The process covers two scenarios:
     * - Users who already have scopes in their token/authentication.
     * - Users who need to load and validate their scopes manually.
     *
     * If the user does not have sufficient permissions, an error response
     * with HTTP status code 403 is returned. If an unexpected exception occurs during the process,
     * an HTTP 500 error is returned with the exception message.
     *
     * @param Request $request Instance of the current HTTP request.
     * @param Closure $next Closure representing the next middleware or action to be executed.
     *
     * @return Response Returns the corresponding HTTP response:
     * - Continues execution if the user has valid permissions.
     * - JSON error response if validation fails or an exception occurs.
     *
     * @warning
     * This method depends on the correct structure of the authenticated object returned
     * by `$request->user()` and on the existence of helper methods such as:
     * `getUser`, `validateAdminScopesUser`, and `isAdmin`.
     */
    public function handle(Request $request, Closure $next): Response
    {

        $responseValidation = [
            'status' => 'Error',
            'statusCode' => 401,
            'message' => 'Unauthorized',
        ];

        try {

            // Retrieves information about the authenticated user
            $userInfo = $request->user();

            // Check if the user does not have any scopes defined
            if (!isset($userInfo->scopes)) {

                // Load additional user information
                $this->getUser($userInfo);

                // Check if the user has administrative permissions
                $validateScopesUser = $this->validateAdminScopesUser($userInfo);

                // Allows the request to proceed if validation is successful
                if ($validateScopesUser) {
                    return $next($request);
                } else {
                    $responseValidation['message'] = config('messages.permissionErrorMessage');
                    $responseValidation['statusCode'] = 403;
                }
            } else {
                // Directly validate the user's existing scopes
                if ($this->isAdmin($userInfo->scopes)) {
                    // Authorized user, continue execution
                    return $next($request);
                } else {
                    $responseValidation['message'] = config('messages.permissionErrorMessage');
                    $responseValidation['statusCode'] = 403;
                }
            }
        } catch (\Throwable $th) {
            $responseValidation['message'] = $th->getMessage();
            $responseValidation['statusCode'] = 500;
        }

        return ApiResponse::response(
            $responseValidation['status'],
            $responseValidation['message'],
            [],
            $responseValidation['statusCode']
        );
    }

    /**
     * isAdmin
     *
     * @param  array $scopes
     * @return bool
     */
    private function isAdmin(array $scopes): bool
    {
        return in_array(config('scopes.admin'), $scopes);
    }

    /**
     * @brief Checks whether a user has administrative scopes.
     *
     * @details
     * This method retrieves the scopes associated with the user from the
     * `UserScopesRepository` and verifies whether any of them correspond to
     * administrative permissions.
     *
     * If the user has valid permissions, the retrieved scopes are dynamically
     * assigned to the `$userInfo` object.
     *
     * If no associated scopes are found or if the user does not have
     * administrative permissions, the validation returns `false`.
     *
     * @param object $userInfo Object containing the authenticated user's information.
     *                         It must include at least the `id` property.
     *
     * @return bool
     * Returns:
     * - `true` if the user has valid administrative scopes.
     * - `false` if they do not have permissions or there are no associated scopes.
     *
     * @note
     * This method modifies the `$userInfo` object by adding the `scopes` property
     * when validation is successful.
     */
    private function validateAdminScopesUser(object $userInfo): bool
    {
        $validateScope = true;
        // Retrieves the scopes associated with the user from the repository
        $getScopesUser = \App\Repositories\UserScopesRepository::getUserScopes([
            'user_id' => $userInfo->id,
        ], ['scope_id']);
        // Verify that the query was successful and contains data
        if ($getScopesUser['status'] && !empty($getScopesUser['data'])) {
            // Extract only the scope identifiers
            $userScopes = array_column($getScopesUser['data']->toArray(), 'scope_id');
            if ($this->isAdmin($userScopes)) {
                // Assign the scopes to the user object for later reuse
                $userInfo->scopes = $userScopes;
            } else {
                // The user does not have administrative permissions
                $validateScope = false;
            }
        } else {
            // No associated scopes were found, or the query failed
            $validateScope = false;
        }

        return $validateScope;
    }

    /**
     * @brief Retrieves and assigns the ID of the authenticated user.
     *
     * @details
     * This method checks whether the `$userInfo` object contains the `id` property.
     * If it does not exist, it queries the `UserRepository`
     * using the user's email address to retrieve their ID.
     *
     * If the query is successful and associated information is found, the retrieved `id`
     * is dynamically assigned to the `$userInfo` object.
     *
     * If an error occurs during the query to the repository, an exception is thrown
     * with the message returned by the service.
     *
     * @param object $userInfo Object containing information about the authenticated user.
     *                         Must contain the `email` property when the `id`
     *                         is not defined.
     *
     * @return void
     *
     * @throws \Exception
     * Thrown when an error occurs during the query to the user repository.
     *
     * @note
     * This method modifies the `$userInfo` object by adding the `id` property
     * when it does not initially exist.
     */
    public function getUser(object $userInfo)
    {
        // Check if the user object does not contain an identifier
        if (!isset($userInfo->id)) {
            // Look up the user using their email address
            $getUser = \App\Repositories\UserRepository::getUser(['email' => $userInfo->email], ['id']);
            // Verify that the query was successful and returned results
            if ($getUser['status'] && !empty($getUser['data'])) {
                // Assigns the retrieved identifier to the user object
                $userInfo->id = $getUser['data']->id;
            } else {
                // Throw an exception only if the query failed
                if (!$getUser['status']) {
                    throw new \Exception($getUser['message']);
                }
            }
        }
    }
}
