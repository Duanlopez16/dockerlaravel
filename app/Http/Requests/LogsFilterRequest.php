<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;

class LogsFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'level' => ['nullable', 'array'],
            'level.*' => ['in:INFO,ERROR,WARNING,ALERT,CRITICAL,EMERGENCY'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string'],
            'date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) {

                $date = Carbon::parse($value);

                $start = Carbon::now()->subDays(config('logging.channels.daily.days'));
                $end = Carbon::now();

                if (!$date->between($start, $end)) {
                    $fail('The date is outside the allowed range.');
                }
            }],
        ];
    }

    /**
     * @brief Normalizes the parameters before performing validation.
     *
     * @return void
     *
     * @note
     * This method is automatically executed by Laravel before
     * applying the rules defined in `rules()`.
     *
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'level' => array_map('strtoupper', explode(',', $this->level))
        ]);
    }

    /**
     * @brief Handles validation failure responses for the request.
     *
     * @details
     * This method overrides Laravel's default behavior
     * when a validation error occurs.
     *
     * Instead of redirecting or returning the framework's standard response,
     * an `HttpResponseException` is thrown with a custom response
     * using `ApiResponse::response`.
     *
     * The response includes:
     * - Error status.
     * - General validation message.
     * - Detailed list of errors per field.
     * - HTTP code 422 (Unprocessable Entity).
     *
     * @param Validator $validator Instance of the validator containing
     *                              the errors generated during validation.
     *
     * @return void
     *
     * @throws HttpResponseException
     * Thrown when the request validation fails.
     *
     * @note
     * This method is automatically executed by Laravel
     * upon detecting validation errors in Form Requests.
     *
     * @warning
     * Request execution stops immediately
     * after the exception is thrown.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::response('error', 'Validation error', $validator->errors()->toArray(), 422)
        );
    }
}
