<?php

namespace App\Repositories;

use App\Models\Mysql\Casper\User;


class UserRepository
{

    /**
     * @brief Retrieves a user from the database based on specified conditions.
     *
     * @details
     * This function queries the database using a set of conditions
     * to filter the user. It allows you to select specific columns or all columns by default.
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
     *               - data (array|object): Data for the user found or an empty array.
     *               - error (bool): Indicates whether an error occurred.
     */
    public static function getUser(array $conditions, array $columns = ['*']): array
    {
        $responseUser = [
            'status' => false,
            'message' => 'error',
            'error' => true,
            'data' => []
        ];
        try {
            $user = User::on(User::NAMECONNECTION)
                ->where($conditions)
                ->select($columns)
                ->first();

            $responseUser = [
                'status' => true,
                'message' => $user ? 'success' : 'notfound',
                'data' => $user ?? [],
                'error' => false,
            ];
        } catch (\Throwable $ex) {
            \Log::error('DB ERROR UserRepository->getUser: ' . $ex->getMessage());
            $responseUser['message'] = $ex->getMessage();
        }

        return $responseUser;
    }
}
