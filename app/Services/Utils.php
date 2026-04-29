<?php

namespace App\Services;

/**
 * Utils
 */
class Utils
{

    /**
     * @brief Calculates the difference between two dates.
     *
     * @details
     * This method takes two dates in string format and calculates the difference between them
     * using PHP's native \DateTime class. The result includes the difference
     * broken down into years, months, and days, as well as a direction indicator (`invert`)
     * that determines whether the final date is earlier than the initial one.
     * 
     * The response is returned in a standardized structure that includes status,
     * message, and data. In case of an error (e.g., invalid date format),
     * the exception is caught and a failure status is returned.
     *
     * @param string $startDate Start date in a format valid for \DateTime.
     * @param string $endDate End date in a format valid for \DateTime.
     *
     * @return array Associative array with the following structure:
     *               - status (bool): indicates whether the operation was successful.
     *               - message (string): result message (‘success’ or ‘error’).
     *               - data (array): contains:
     *                   - years (int): difference in years.
     *                   - months (int): difference in months.
     *                   - months (int): duplicate of months (possible typo retained for compatibility).
     *                   - days (int): difference in days.
     *                   - invert (int): 1 if the end date is earlier than the start date, 0 otherwise.
     *
     * @warning
     * - Dates must be in a format valid for \DateTime.
     * - The format is not explicitly validated before instantiating the objects.
     */
    public static function diffDates(string $startDate, string $endDate): array
    {

        $responseDiff = [
            'status' => true,
            'message' => 'success',
            'data' => []
        ];
        $result = [
            'years' => 0,
            'months' => 0,
            'days' => 0,
        ];

        try {

            // Create DateTime objects from the received dates
            $date1 = new \DateTime($startDate);
            $date2 = new \DateTime($endDate);

            // Calculate the difference between the two dates
            $diff = $date1->diff($date2);

            $result['years'] = $diff->y;
            $result['moths'] = $diff->m;
            $result['months'] = $diff->m;
            $result['days'] = $diff->d;

            // Indicates whether the end date is earlier than the start date (1 = reversed)
            $result['invert'] = $diff->invert;

            $responseDiff['data'] = $result;
        } catch (\Throwable $ex) {
            $responseDiff['status'] = false;
            $responseDiff['message'] = $ex->getMessage();
        }

        return $responseDiff;
    }
}
