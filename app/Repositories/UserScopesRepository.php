<?php

namespace App\Repositories;

use App\Models\Mysql\Casper\UserScopes;


class UserScopesRepository
{

    /**
     * @brief Retrieves a userScopes from the database based on specified conditions.
     *
     * @details
     * This function queries the database using a set of conditions
     * to filter the userScope. It allows you to select specific columns or all columns by default.
     * Returns a structured array containing the operation status, a message,
     * the user data (if found), and an error indicator.
     *
     * If an error occurs during the query, the exception is caught, logged,
     * and an error message is returned along with the corresponding status.
     *
     * @param array $conditions Array of conditions to filter the user search (WHERE clause).
     * @param array $columns Array of columns to select in the query. By default, selects all (*).
     *
     * @return array Array containing the operation's response:
     *               - status (bool): Indicates whether the operation was successful.
     *               - message (string): Result message (‘success’, ‘notfound’, or ‘error’).
     *               - data (array|object): Data for the userScope found or an empty array.
     *               - error (bool): Indicates whether an error occurred.
     */
    public static function getUserScope(array $conditions, array $columns = ['*']): array
    {
        $responseUserScope = [
            'status' => false,
            'message' => 'error',
            'error' => true,
            'data' => []
        ];
        try {
            $userScope = UserScopes::on(UserScopes::NAMECONNECTION)
                ->where($conditions)
                ->select($columns)
                ->first();

            $responseUserScope = [
                'status' => true,
                'message' => $userScope ? 'success' : 'notfound',
                'data' => $userScope ?? [],
                'error' => false,
            ];
        } catch (\Throwable $ex) {
            \Log::error('DB ERROR UserScopesRepository->getUserScope: ' . $ex->getMessage());
            $responseUserScope['message'] = $ex->getMessage();
        }

        return $responseUserScope;
    }

    /**
     * @brief Retrieves the scopes associated with a user from the database.
     *
     * @details
     * This method queries the user scopes table using
     * the filters provided in `$conditions`.
     *
     * The query allows you to select specific columns and uses
     * the connection defined in the `UserScopes` model.
     *
     * The method returns a standard structure indicating:
     * - Operation status.
     * - Descriptive message.
     * - Retrieved data.
     * - Error indicator.
     *
     * In case of an exception during the query, the error is logged
     * in the system logs and a controlled response is returned.
     *
     * @param array $conditions Conditions used in the `where` clause.
     *                          Example:
     *                          - `[‘user_id’ => 1]`
     *
     * @param array $columns List of columns to select.
     *                       Default value: `[‘*’]`.
     *
     * @return array
     * Returns an array containing:
     * - `status` : Operation status.
     * - `message`: Query result.
     * - `error`  : Boolean error indicator.
     * - `data`   : Collection of scopes found.
     *
     * @note
     * The method uses Eloquent ORM to build and execute the query.
     *
     * @warning
     * If a database exception occurs,
     * the message will be logged in the system logs.
     */
    public static function getUserScopes(array $conditions, array $columns = ['*']): array
    {
        $responseUserScopes = [
            'status' => false,
            'message' => 'error',
            'error' => true,
            'data' => []
        ];
        try {
            $userScopes = UserScopes::on(UserScopes::NAMECONNECTION)
                ->where($conditions)
                ->select($columns)
                ->get();

            $responseUserScopes = [
                'status' => true,
                'message' => $userScopes ? 'success' : 'notfound',
                'data' => $userScopes ?? [],
                'error' => false,
            ];
        } catch (\Throwable $ex) {
            \Log::error('DB ERROR UserScopesRepository->getUserScopes: ' . $ex->getMessage());
            $responseUserScopes['message'] = $ex->getMessage();
        }

        return $responseUserScopes;
    }
}
