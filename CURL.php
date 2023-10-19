<?php 

namespace App\Api;
class CURL
{
    public $address;
    public $port = 443;
    public function __construct($address)
    {
        $this->ch = curl_init($address);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
        $this->address = $address;
    }
    public function addurl($extra_uri)
    {
        $this->address = $this->address . $extra_uri;
        curl_setopt($this->ch, CURLOPT_URL, $this->address);
    }
    public function set_method($method, $params = [])
    {
        switch ($method)
        {
            case 'POST':
                curl_setopt_array($this->ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($params) ]);
            break;
            case 'GET':
                curl_setopt_array($this->ch, [CURLOPT_CUSTOMREQUEST => 'GET', CURLOPT_URL => $this->address . '?' . self::convert_to_query($params) ]);
            break;
            case 'PUT':
                curl_setopt_array($this->ch, [CURLOPT_CUSTOMREQUEST => 'PUT', CURLOPT_POSTFIELDS => http_build_query($params) ]);
            break;
            case 'DELETE':
                curl_setopt_array($this->ch, [CURLOPT_CUSTOMREQUEST => 'DELETE', CURLOPT_POSTFIELDS => http_build_query($params) ]);
            break;
        }
    }
    public function set_header($headers)
    {
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, self::convert_to_standardheader($headers));
    }
    public function set_port($port)
    {
        curl_setopt($this->ch, CURLOPT_PORT, $port);
    }
    public function set_timeout($timeout)
    {
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);
    }
    public function set_proxy($address, $port, $type = 'CURLPROXY_HTTP')
    {
        curl_setopt($this->ch, CURLOPT_PROXY, $address . ':' . $port);
        curl_setopt($this->curl, CURLOPT_HTTPPROXYTUNNEL, true);
        curl_setopt($this->curl, CURLOPT_PROXYTYPE, $type);
    }
    private static function convert_to_query($params)
    {
        $num = 0;
        $query = '';
        $keys = array_keys($params);
        $values = array_values($params);
        foreach ($params as $parameter)
        {
            $query .= $keys[$num] . '=' . $values[$num] . '&&';
            $num++;
        }
    }
    private static function convert_to_standardheader($params)
    {
        $query = [];
        $num = 0;
        $keys = array_keys($params);
        $values = array_values($params);
        foreach ($params as $parameter)
        {
            array_push($query, $keys[$num] . ': ' . $values[$num]);
            $num++;
        }
        return $query;
    }
    public function addopt($options)
    {
        if (is_array($options)) curl_setopt_array($this->ch, $options);
        else curl_setopt($this->ch, array_keys($options) [0], array_values($options) [0]);
    }
    public function execute()
    {
        $this->response = curl_exec($this->ch);
        $this->httpcode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        if (!empty(curl_errno($this->ch))):
            $this->errorcode = curl_errno($this->ch);
            $this->error = curl_error($this->ch);
            file_put_contents("error.txt", $this->error);
        endif;
    }
    public function close()
    {
        curl_close($this->ch);
    }
    public function __destruct()
    {
        curl_close($this->ch);
    }
}