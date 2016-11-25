<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exceptions\APIException;
use Illuminate\Contracts\Validation\Validator;

class FileController extends Controller {

    /**
     * __construct
     */
    public function __construct() {
        return parent::__construct();
    }

    /**
     * create
     *
     * @param  Request $request
     * @return
     */
    public function upload(Request $request) {
        try {
            $file = $request->file();
            if (!$file || !$file['source']) {
                throw new APIException('文件不能为空');
            }

            $sourceFile = $file['source'];

            $filePath = $sourceFile->path();
            $fileName = $sourceFile->getOriginalName();
            $type = 'attachment';

            // 附件上传 任务或者评论的附件
            $result = \App\File::upload($filePath, $fileName, $type);

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($result);
    }
}