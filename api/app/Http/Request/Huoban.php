<?php

namespace App\Http\Request;

class Huoban
{
    static $dev, $token, $url, $clientId, $clientSecret, $ch, $headers, $ticket;

    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';

    static $apiInfo = array(
        'used_time' => 0,
        'urls' => array(),
    );

    public static function setup($ticket = '') {

        // Setup curl
        self::$url = IS_TEST ? 'https://api-dev.huoban.com' : 'https://api.huoban.com';
        self::$ch = curl_init();

        self::$headers = array(
            'Accept' => 'application/json'
        );

        self::$token = $token;
        self::$ticket = $ticket;

        curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt(self::$ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt(self::$ch, CURLOPT_USERAGENT, 'Huoban PHP Client/3.0');
        curl_setopt(self::$ch, CURLOPT_HEADER, true);
        curl_setopt(self::$ch, CURLINFO_HEADER_OUT, true);
    }

    public static function request($method, $url, $attributes = array(), $options = array()) {

        $startTime = microtime(true);

        if (!self::$ch) {
            throw new \Exception('Client has not been setup with client id and client secret.');
        }

        curl_setopt(self::$ch, CURLOPT_POSTFIELDS, null);

        switch ($method) {
            case self::GET:
                curl_setopt(self::$ch, CURLOPT_CUSTOMREQUEST, self::GET);
                self::$headers['Content-type'] = 'application/x-www-form-urlencoded';
                if ($attributes) {
                    $query = self::encodeAttributes($attributes);
                    $url = $url.'?'.$query;
                }
                // self::$headers['Content-length'] = "0";
                break;
            case self::DELETE:
                curl_setopt(self::$ch, CURLOPT_CUSTOMREQUEST, self::DELETE);
                self::$headers['Content-type'] = 'application/x-www-form-urlencoded';
                $query = self::encodeAttributes($attributes);
                if ($query) {
                    $url = $url.'?'.$query;
                }
                // self::$headers['Content-length'] = "0";
                break;
            case self::POST:
                curl_setopt(self::$ch, CURLOPT_CUSTOMREQUEST, self::POST);
                if (!empty($options['upload'])) {

                    // php5.6之后的只能使用这个方法
                    $file = curl_file_create($attributes['source'], '', $attributes['name']);

                    $attributes['source'] = $file;
                    curl_setopt(self::$ch, CURLOPT_POST, TRUE);
                    curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $attributes);
                    self::$headers['Content-type'] = 'multipart/form-data';
                } elseif (!empty($options['oauth_request'])) {
                    // application/json
                    $encodedAttributes = json_encode($attributes);
                    curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $encodedAttributes);
                    self::$headers['Content-type'] = 'application/json';
                } else {
                    curl_setopt(self::$ch, CURLOPT_POST, TRUE);
                    $encodedAttributes = json_encode($attributes);
                    curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $encodedAttributes);
                    self::$headers['Content-type'] = 'application/json';
                }
                break;
            case self::PUT:
                $encodedAttributes = json_encode($attributes);
                curl_setopt(self::$ch, CURLOPT_CUSTOMREQUEST, self::PUT);
                curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $encodedAttributes);
                self::$headers['Content-type'] = 'application/json';
                break;
        }

        // Add access token to request
        if (self::$token) {
            $token = trim(self::$token);
            self::$headers['Authorization'] = "Bearer {$token}";
        } else {
            unset(self::$headers['Authorization']);
        }

        if (self::$ticket) {
            $ticket = trim(self::$ticket);
            self::$headers['X-Huoban-Ticket'] = $ticket;
        } else {
            unset(self::$headers['Authorization']);
        }

        if (isset($options['headers']) && $options['headers']) {
            foreach ($options['headers'] as $key => $value) {
                self::$headers[$key] = $value;
            }
        }

        // Add x-return-fields
        $headers = self::$headers;
        if (isset($options['fields'])) {
            $headers['X-Huoban-Return-Fields'] = json_encode($options['fields']);
        }

        curl_setopt(self::$ch, CURLOPT_HTTPHEADER, self::curlHeaders($headers));
        if (substr($url, 0, 4) == 'http') {
            $requestUrl = $url;
        } else {
            $requestUrl = self::$url . $url;
        }
        curl_setopt(self::$ch, CURLOPT_URL, $requestUrl);

        // 将请求转换为curl 直接复制日志中的curl就能调试错误  记录到日志中
        $requestCurlString = self::getCurlCommand($requestUrl, $method, self::$headers, $attributes);
        \Log::info('request:' . $requestCurlString);

        $rawResponse = curl_exec(self::$ch);
        $rawHeadersSize = curl_getinfo(self::$ch, CURLINFO_HEADER_SIZE);
        $status = curl_getinfo(self::$ch, CURLINFO_HTTP_CODE);

        $responseContent = substr($rawResponse, $rawHeadersSize);
        if ($responseContent === false) {
            $responseContent = '';
        }

        $response = response($responseContent, $status);

        $statusCode = $response->getStatusCode();
        // 日志记录响应
        \Log::info('response:statusCode-' . $statusCode . ';content-' . $responseContent);

        switch ($statusCode) {
            case 200 :
            case 201 :
            case 204 :
                return $response;
                break;
            case 400 :
            case 401 :
            case 403 :
            case 404 :
            case 409 :
            case 410 :
            case 420 :
            case 500 :
            case 502 :
            case 503 :
            case 504 :
            default :
                $body = $response->getContent();
                if ($body) {
                    $body = json_decode($body, true);
                    $code = $body['code'];
                    $message = $body['message'];
                    $errors = $body['errors'];
                } else {
                    $code = 0;
                    $message = 'api请求错误 url:' . $url;
                    $errors = array();
                }
                throw new \App\Exceptions\RequestException($message, $code, $errors);
                break;
        }
        return false;
    }

    public static function get($url, $attributes = array(), $options = array()) {
        return self::request(Huoban::GET, $url, $attributes, $options);
    }
    public static function post($url, $attributes = array(), $options = array()) {
        return self::request(Huoban::POST, $url, $attributes, $options);
    }
    public static function put($url, $attributes = array(), $options = array()) {
        return self::request(Huoban::PUT, $url, $attributes, $options);
    }
    public static function delete($url, $attributes = array()) {
        return self::request(Huoban::DELETE, $url, $attributes);
    }

    public static function curlHeaders($curlHeaders) {
        foreach ($curlHeaders as $header => $value) {
            $headers[] = "{$header}: {$value}";
        }
        return $headers;
    }

    public static function encodeAttributes($attributes) {
        $result = '';

        if (is_array($attributes)) {
            $result = http_build_query($attributes, '', '&');
        }

        return $result;
    }

    public static function getCurlCommand($requestUrl, $requestMethod, $requestHeaders, $requestParams) {

        $curlCommand = 'curl ';
        $postData = $getData = '';

        if ($requestMethod == 'GET') {
            // $getData .= ('?' . http_build_query($requestParams, '', '&'));
        } else {
            $postData = " -X {$requestMethod} --data-binary '" . str_replace("'", "\'", json_encode($requestParams)) . "'";
        }

        $curlCommand .= '\'' . $requestUrl . $getData . '\'';

        $curlCommand .= $postData;

        foreach ($requestHeaders as $key => $value) {
            $curlCommand .= ' -H \'' . $key . ':' . $value . '\'';
        }

        return $curlCommand;
    }
}
