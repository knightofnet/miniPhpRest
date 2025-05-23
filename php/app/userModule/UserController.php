<?php

namespace MiniPhpRest\app\userModule;

use MiniPhpRest\core\AbstractController;
use MiniPhpRest\core\ResponseObject;

class UserController extends AbstractController
{

    public function index() : ResponseObject
    {
        $retMsg = [
            'result' => true,
            'content'=> [
                'users' => [
                    ['id' => 1, 'name' => 'John Doe'],
                    ['id' => 2, 'name' => 'Jane Doe'],
                    ['id' => 3, 'name' => 'Jim Doe'],
                ]
            ],
            'message'=> ''];

        return ResponseObject::ResultsObjectToJson($retMsg, 200);
        //return ResponseObject::ResultCodeHttp(500);
    }

    public function show(int $test) : ResponseObject {
        echo 'Show user'.$test;

        return ResponseObject::ResultCodeHttp(500);
    }

    public function login() : ResponseObject {

        $retMsg = ['result' => false, 'message'=> 'Error unknown'];
        $codeStatus = 500;

        if (!empty($this->getRequest()->getBodyJson())) {
            $userId = $this->getRequest()->getBodyJson()['id'];
            $codePin = $this->getRequest()->getBodyJson()['pinCode'];

            if ($userId == 1 && intval($codePin) == 1234) {
                $retMsg = ['result' => true, 'message'=> 'Login success'];
                $codeStatus = 200;
            } else {
                $retMsg = ['result' => false, 'message'=> 'Login failed'];
                $codeStatus = 401;
            }

        }

        return ResponseObject::ResultsObjectToJson($retMsg, $codeStatus);
    }

}