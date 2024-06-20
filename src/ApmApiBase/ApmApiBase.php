<?php
namespace ApmApiBase;

use \Psr\Http\Message\ResponseInterface;
use \GuzzleHttp\Exception\RequestException;


Class ApmApiBase {

    private $client = null;

    CONST CONFIG_TOKEN_PATH = '';
    public static $url = '';
    public static $domain = '';

    public $defaultHeader = [];


    function __construct($token)
    {
        $this->client = $this->init($token);
    }

    static public function __getToken($token = null)
    {
        if (empty($token)) {
            if (function_exists('config') && !empty(config(static::CONFIG_TOKEN_PATH))) {
                $token = config(static::CONFIG_TOKEN_PATH);
            } elseif (!function_exists('config')) {
                die(' config not function_exists');
            } elseif (empty(config(static::CONFIG_TOKEN_PATH))) {
                die(' config(\'' . static::CONFIG_TOKEN_PATH . '\') is empty');
            } else {
                die('UNKNOWN ERROR config(\'' . static::CONFIG_TOKEN_PATH . '\')');
            } 
        }

        return $token;
    }

    static public function init($token = null)
    {
        if (empty($token)) {
            $token = static::__getToken($token);
        }
        return new \GuzzleHttp\Client(['timeout' => 0, 'headers' => [
            'Authorization' => $token,
        ],]);
    }

    public static function api( &$postData, $token = null, $debug = false)
    {
        $client = self::init($token);

        $flatten = function($array, $original_key = '') use (&$flatten) {
            $output = [];
            foreach ($array as $key => $value) {
                $new_key = $original_key;
                if (empty($original_key)) {
                    $new_key .= $key;
                } else {
                    $new_key .= '[' . $key . ']';
                }
                if (is_array($value)) {
                    $output = array_merge($output, $flatten($value, $new_key));
                } else {
                    $output[$new_key] = $value;
                }
            }
            return $output;

        };


        $flat_array = $flatten(['queries' => $postData]);

        $data = [];
        foreach($flat_array as $key => $value) {
            $data[] = [
                'name'  => $key,
                'contents' => $value
            ];
        }
        $sendData = [ 'multipart' => $data,];

        /* if ($debug == 3) {
             $jar = \GuzzleHttp\Cookie\CookieJar::fromArray([
                 'XDEBUG_SESSION' => '1'
             ], static::$domain);
             $sendData['cookies'] = $jar;
         }*/

        $response = $client->request( 'POST', static::$url, $sendData);
        if (method_exists($response, 'getBody')) {
            $decodeResult = json_decode($response->getBody()->getContents(), true);
        } else {
            die('method not exists($response, \'getBody\')');
        }

        return $decodeResult;
    }



}