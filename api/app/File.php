<?php

namespace App;

use App\BaseModel;

class File extends BaseModel
{

    public static function upload($filePath, $fileName, $type) {

        $response = \App\Http\Request\Huoban::post('/v2/file', array('source' => realpath($filePath), 'name' => $fileName, 'type' => $type), array('upload' => TRUE));
        $result = $response->getContent();

        $result = json_decode($result, true);
        return $result;
    }
}
