<?php

namespace MiniPhpRest\core\utils;

class ResponseUtils
{

    private function __construct()
    {
    }

    public static function getDefaultResponseArray(
        $result = false,
        $data = [],
        $type = 'unknown',
        $errorMsg = 'Error unknown',
        $errCode = 1): array
    {

        $dft = ['result' => $result,
            'content' => [
                'type' => $type,
                'data' => $data
            ],
            'error' => [
                'msg' => 'Error unknown',
                'code' => 1
            ]
        ];

        if ($result) {
            unset($dft['error']);
        } else {
            $dft['error']['msg'] = $errorMsg;
            $dft['error']['code'] = $errCode;
        }

        return $dft;

    }

}