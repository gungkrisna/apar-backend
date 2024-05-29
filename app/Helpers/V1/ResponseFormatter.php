<?php

namespace App\Helpers\V1;

class ResponseFormatter
{
    protected static $response = [
        'code' => 200,
        'status' => 'OK',
        'data' => null,
        'errors' => null
    ];

    public static function success($code = 200, $status = 'OK', $data = null)
    {
        self::$response['code'] = $code;
        self::$response['status'] = $status;
        $data && self::$response['data'] = $data;

        return response()->json(self::$response, self::$response['code']);
    }

    public static function error($code = 400, $status = 'Bad Request', $errors = null)
    {
        self::$response['code'] = $code;
        self::$response['status'] = $status;
        $errors && self::$response['errors'] = $errors;

        return response()->json(self::$response, self::$response['code']);
    }
}
