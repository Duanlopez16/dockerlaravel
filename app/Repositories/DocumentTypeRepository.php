<?php

namespace App\Repositories;

use App\Models\Mysql\Subscriptions\DocumentType;


class DocumentTypeRepository
{

    /**
     * @brief Retrieves a document type from the database based on specified conditions.
     *
     * @details
     * This function queries the database using the DocumentType model,
     * applying a set of conditions to filter the results. It allows you to select
     * specific columns or all columns by default.
     *
     * Returns a structured array indicating whether the operation was successful, a descriptive
     * message, the data found (if any), and an error indicator.
     *
     * If an exception occurs during the query, the error is logged
     * and the corresponding message is returned.
     *
     * @param array $conditions Array of conditions to filter the search (WHERE clause).
     * @param array $columns Array of columns to select in the query. Default [‘*’].
     *
     * @return array Array containing the operation's response:
     *               - status (bool): Indicates whether the operation was successful.
     *               - message (string): ‘success’, ‘notfound’, or an error message.
     *               - data (object|array): Result found or an empty array.
     *               - error (bool): Indicates whether an error occurred.
     */
    public static function getDocumentType(array $conditions, array $columns = ['*']): array
    {
        $responseDocumentType = [
            'status' => false,
            'message' => 'error',
            'error' => true,
            'data' => []
        ];
        try {
            $user = DocumentType::on(DocumentType::NAMECONNECTION)
                ->where($conditions)
                ->select($columns)
                ->first();

            $responseDocumentType = [
                'status' => true,
                'message' => $user ? 'success' : 'notfound',
                'data' => $user ?? [],
                'error' => false,
            ];
        } catch (\Throwable $ex) {
            \Log::error('DB ERROR DocumentTypeRepository->getDocumentType: ' . $ex->getMessage());
            $responseDocumentType['message'] = $ex->getMessage();
        }

        return $responseDocumentType;
    }
}
