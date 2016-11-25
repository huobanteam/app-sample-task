<?php

namespace App;

use App\BaseModel;

class User extends BaseModel
{

    static $loggedUser;

    public static function getLoggedUser() {
        if (self::$loggedUser) {
            return $loggedUser;
        }

        $response = \App\Http\Request\Huoban::get('/v2/user');
        $result = $response->getContent();
        $result = json_decode($result, true);

        self::$loggedUser = $result;

        return $result;
    }
}
