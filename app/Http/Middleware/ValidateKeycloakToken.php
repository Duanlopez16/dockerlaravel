<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Sso;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use DomainException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use App\Support\ApiResponse;

class ValidateKeycloakToken
{
    public function __construct(
        private Sso $service
    ) {}

    /**
     * Middleware that intercepts the HTTP request to validate a Bearer token.
     * If the token is valid, it injects the user information into the request.
     *
     * @param Request $request  Incoming request
     * @param Closure $next     Next middleware/controller in the chain
     * @return Response         HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        /**
         * Retrieves the Bearer token from the Authorization header.
         * Expected example:
         * Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
         */
        $token = $request->bearerToken();

        /**
         * Checks whether the token is empty or was not sent
         */
        if (blank($token)) {
            return ApiResponse::response('Error', 'Token not provided', [], 401);
        }

        try {
            //Decode and validate the token using the appropriate service
            $decodedToken = $this->service->decodeAndValidate($token);

            /**
             * Inserts the authenticated user's information into the request
             * so it can be used in subsequent controllers.
             * Example of accessing it later:
             * $request->get(‘auth_user’)
             */
            $request->setUserResolver(fn() => $decodedToken);

            //Proceed to the next middleware or controller
            return $next($request);
        } catch (Throwable $e) {
            /**
             * Catches any errors that occur during token validation:
             * - Invalid token
             * - Expired token
             * - Internal service error
             */
            return $this->handleException($e);
        }
    }

    /**
     * Handles exceptions related to token validation
     * and returns an appropriate HTTP response based on the error type.
     *
     * @param Throwable $e Exception caught during the authentication process
     * @return Response    Standardized HTTP response
     */
    private function handleException(Throwable $e): Response
    {
        return match (true) {
            $e instanceof ExpiredException => ApiResponse::response('Error', 'Token has expired', [], 401),
            $e instanceof SignatureInvalidException => ApiResponse::response('Error', 'Invalid signature', [], 401),
            $e instanceof BeforeValidException => ApiResponse::response('Error', 'Token invalid', [], 401),
            $e instanceof DomainException => ApiResponse::response('Error', $e->getMessage(), [], 400),
            default => ApiResponse::response('Error', $e->getMessage(), [], 500),
        };
    }
}
