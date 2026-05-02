<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Return a successful JSON response
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data = null, $message = 'Success', $code = 200)
    {
        $response = [
            'status' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        // JSON_INVALID_UTF8_SUBSTITUTE: replace invalid UTF-8 for strict parsers (e.g. Dart)
        // JSON_UNESCAPED_UNICODE: output UTF-8 directly instead of \uXXXX to avoid invalid escape sequences
        $options = (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0)
            | JSON_UNESCAPED_UNICODE;
        return response()->json($response, $code, [], $options);
    }

    /**
     * Return an error JSON response
     *
     * @param string $message
     * @param int $code
     * @param array $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error($message = 'An error occurred', $code = 400, $errors = [])
    {
        $response = [
            'status' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a validation error JSON response
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationError($validator, $message = 'Validation failed')
    {
        return $this->error($message, 422, $validator->errors()->toArray());
    }

    /**
     * Return an unauthorized JSON response
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorized($message = 'Unauthorized')
    {
        return $this->error($message, 401);
    }

    /**
     * Return a forbidden JSON response
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbidden($message = 'Forbidden')
    {
        return $this->error($message, 403);
    }

    /**
     * Return a not found JSON response
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFound($message = 'Resource not found')
    {
        return $this->error($message, 404);
    }
}

