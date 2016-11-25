<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController {

    protected $_table;

    public function __construct() {

        if (!TABLE_ID) {
            $result = array(
                'code' => '500',
                'message' => 'X-Task-Table-Id cannot be empty',
                'errors' => array(),
            );

            return response()->json($result, 500)->send();
        }
    }

    protected function _getTable() {
        if ($this->_table) {
            return $this->_table;
        }

        $table = \App\Table::get(TABLE_ID);
        $this->_table = $table;

        return $table;
    }

    /**
     * _handleException 返回错误信息
     *
     * @param  $e
     * @return json_string
     */
    protected function _handleException($e) {

        $errors = array();

        if ($e instanceof \App\Exceptions\RequestException) {
            $errors = $e->errors;
        }

        $result = array(
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'errors' => $errors,
        );

        return response()->json($result, 500);
    }

    /**
     * _handleValidationException 返回验证错误信息
     *
     * @param $e
     * @return json_string
     */
    protected function _handleValidationException($e) {

        $messages = $e->response->getContent();
        if ($messages) {
            $messages = json_decode($messages, true);
            $rebuildMessages = array();
            foreach ($messages as $values) {
                $rebuildMessages[] = implode(' ', $values);
            }
            $message = implode(' ', $rebuildMessages);

        } else {
            $message = $e->getMessage();
        }

        $result = array(
            'code' => $e->getCode(),
            'message' => $message,
            'errors' => array(),
        );

        return response()->json($result, 500);
    }

    /**
     * _handleResult 返回正确信息
     *
     * @param   $data
     * @return json_string
     */
    protected function _handleResult($data = null) {

        return response()->json($data, 200);
    }
}
