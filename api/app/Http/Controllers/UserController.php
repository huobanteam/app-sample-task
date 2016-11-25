<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exceptions\APIException;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getLogged(Request $request) {

        try {
            $result = \App\User::getLoggedUser();
        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($result);
    }
}
