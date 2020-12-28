<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function jsonResponse($data = [], $code = 200, $message = 'success') {
        $json = new \stdClass();
        $json->code = $code ? $code : 500;
        $json->message = $message;
        $json->data = $data;

        return response()->json($json, $json->code);
    }

    public function paramsToString($params) {
        $paramsString = implode($params);
        foreach ($params as &$param) {
            if (strpos($param, '|') !== false) {
                $param = explode('|', $param);
            }
        }
        return $paramsString;
    }
}
