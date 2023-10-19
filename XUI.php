<?php 
namespace App\Api;
class XUI
{
    public $address;
    public $username;
    public $password;

    public $status = false;
    public function __construct(string $username, string $password, string $address)
    {
        $this->address = $address;
        $this->username = $username;
        $this->password = $password;
        $this->login();
    }
    public function setStatus($curl){
        if (json::_in($curl->response) !== null && json::_in($curl->response)->success == true){
             $this->status = true;
        }else{ 
             $this->status = false;
        }
    }

    public function Response($data = null){
       return ['status' => $this->status , 'data' => $data];
    }

    static function byteToGig($byte){
        return round((($byte / 1024) / 1024) / 1024, 2);
    }

    public static function convertToBytes($from)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $number = substr($from, 0, -2);
        $suffix = strtoupper(substr($from, -2));

        if (is_numeric(substr($suffix, 0, 1)))
        {
            return preg_replace('/[^\d]/', '', $from);
        }
        $exponent = array_flip($units) [$suffix] ?? null;
        if ($exponent === null)
        {
            return null;
        }

        return $number * (1024**$exponent);
    }
    
    public static function bth(int | float | string $byte, ? string $base = null, int $estimate = 2, bool $decimal = false) : string
    {
        if (!is_numeric($byte)) $byte = self::convertToBytes($byte);
        $divisor = ($decimal ? 1000 : 1024);
        $keys = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB', 'BB'];
        if (!is_null($base) && ($offset = array_search(strtoupper($base) , $keys)) !== false) return round($byte / $divisor**$offset, $estimate) . ' ' . $keys[$offset];
        if (($bronto = $byte / $divisor**9) > 1024) return round($bronto, $estimate) . ' BB';
        for ($key = 0;$byte / $divisor > 0.99;$key++) $byte /= $divisor;
        return round($byte, $estimate) . ' ' . $keys[$key];
    }
    public static function random($length)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $out = '';
        for ($i = 1;$i <= $length;$i++):
            $out .= $chars[rand(0, strlen($chars) - 1) ];
        endfor;
        return $out;
    }
    public static function gen_uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff) , mt_rand(0, 0xffff) ,

        // 16 bits for "time_mid"
        mt_rand(0, 0xffff) ,

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,

        // 48 bits for "node"
        mt_rand(0, 0xffff) , mt_rand(0, 0xffff) , mt_rand(0, 0xffff));
    }

    public static function tcp(){
        return json_encode([
            'network' => 'tcp', 
            'security' => 'none', 
            'tcpSettings' => [
                'header' => [
                    'type' => 'http', 
                    'request' => [
                            'method' => 'GET', 
                            'path' => ['/' . self::random(5) . '/' . self::random(10) ],
                    ],
                    'response' =>[
                        'version' => '1.1', 
                        'status' => '200', 
                        'reason' => 'OK'
                    ]
                ]
            ]
        ]);

    }

    public static function ws(){
        return json_encode([
            'network' => $transport,
             'security' => 'none', 
             'wsSettings' => [
                'path' => '/' . self::random(5) . '/' . self::random(10) 
            ]
        ]);
    }

    public function login()
    {
        $curl = new CURL($this->address . '/login');
        $curl->set_method('POST', ['username' => $this->username, 'password' => $this->password]);
        $curl->execute();
        $this->setStatus($curl);
        $curl->close();
        return $this->Response();
    }
    public function status()
    {
        $curl = new CURL($this->address . '/server/status');
        $curl->set_method('POST');
        $curl->execute();
        $curl->close();
        $this->setStatus($curl);
        if ($this->status)
        {
            $obj = json::_in($curl->response)->obj;
            $sysload = $obj->loads;
            $cpu_count = $obj->cpuNum;
            $cpu_usage = $obj->cpu;
            $mem_usage = $obj->mem->current;
            $mem_total = $obj->mem->total;
            $disk_usage = $obj->disk->current;
            $disk_total = $obj->disk->total;
            $xray_status = $obj->xray->state;
            $uptime = date('j', $obj->uptime); //in Day
            $traffic_up = $obj->netTraffic->sent;
            $traffic_down = $obj->netTraffic->recv;
            $data = [
                'cpu_count' => $cpu_count,
                'cpu_usage' => $cpu_usage,
                'mem_usage' => $mem_usage, 
                'mem_total' => $mem_total, 
                'disk_usage' => $disk_usage, 
                'disk_total' => $disk_total,
                'xray_status' => $xray_status,
                'uptime' => $uptime, 
                'traffic_down' => $traffic_down, 
                'traffic_up' => $traffic_up, 
                'sysload' => $sysload
            ];
            return $this->Response($data);
        }
    }
    public function getProfile($port = null, $service_id = null)
    {
        $curl = new CURL($this->address . '/xui/inbound/list');
        $curl->set_method('POST');
        $curl->execute();
        $this->setStatus($curl);
        $ok = false;
        if ($this->status)
        {
            $result = json::_in($curl->response, true);
            $list = $result['obj'];
            foreach ($list as $profile):
                if ($profile['port'] == $port || $profile['id'] == $service_id)
                {
                    $id = $profile['id'];
                    $name = $profile['remark'];
                    $listen = $profile['listen'];
                    $port = $profile['port'];
                    $uuid = json::_in($profile['settings'], true) ['clients'][0]['id'];
                    $stream_opt = json::_in($profile['streamSettings']);
                    if ($stream_opt->network == 'ws')
                    {
                        $transport = 'ws';
                        $path = $stream_opt
                            ->wsSettings->path;
                    }
                    else
                    {
                        $transport = 'tcp';
                        $path = $stream_opt
                            ->tcpSettings
                            ->header
                            ->request
                            ->path[0];
                    }
                    $upload = self::byteToGig($profile['up']);
                    $download = self::byteToGig($profile['down']);
                    $total = self::byteToGig($profile['total']);

                    if (!empty($profile['expiryTime']) || $profile['expiryTime'] != 0)
                    {
                        $expire = round($profile['expiryTime'] / 1000);
                        $expire_dt = date('Y/m/d H:i', round($profile['expiryTime'] / 1000));
                        $expire_time = round($profile['expiryTime'] / 1000) - time();
                        if ($expire_time > 86400)
                        {
                            $expire_in = round($expire_time / 86400, 0, 1);
                        }
                        else
                        {
                            $expire_in = 0;
                        }
                    }
                    else
                    {
                        $expire_dt = 'Unlimited';
                        $expire_in = 'Unlimited';
                    }
                    $ok = true;
                    break;
                }
            endforeach;
            $curl->close();
            if ($ok)
            {
                $data = [
                'ok' => true,
                'id' => $id, 
                'name' => $name, 
                'address' => $listen, 
                'port' => $port, 
                'uuid' => $uuid, 
                'path' => $path, 
                'transport' => $transport, 
                'total' => $total,
                // in GB
                'up' => $upload,
                // in GB
                'down' => $download,
                // in GB
                'used' => $download + $upload,
                // in GB
                'expire' => $expire ?? 0,
                //unix timestamp
                'expire_date' => $expire_dt,
                // Y/m/d H:i
                'expire_in' => $expire_in
                // depend on time Day/Hours/Minutes will be added to end of number
                ];
            }else{
                $data = [
                    'ok' => false,
                ];
            }
        }
        return $this->Response($data);
    }

    public function add($name, $traffic, $expire, $protocol = "vmess", $transport = "tcp")
    {
        $curl = new CURL($this->address . '/xui/inbound/add');
        $traffic_in_mb = self::convertToBytes($traffic . 'GB');
        $PortGenerator = rand(2000, 65000);
        $ExpiryTimePlus = 86400 * $expire;
        $ExpiryTime = $milliseconds = intval((time() + $ExpiryTimePlus) * 1000);
        if (in_array($transport,['ws','tcp']))
        {
            $transport_setting = self::$transport();
        }
        $curl->set_method('POST', ['up' => 0, 'down' => 0, 'total' => $traffic_in_mb, 'remark' => $name, 'enable' => 'true', 'expiryTime' => $ExpiryTime, 'listen' => null, 'port' => $PortGenerator, 'protocol' => $protocol, 'settings' => json::_out(['clients' => [['id' => self::gen_uuid() , 'alterId' => 0]], 'disableInsecureEncryption' => false]) , 'streamSettings' => $transport_setting, 'sniffing' => json::_out(['enabled' => true, 'destOverride' => ['http', 'tls']]) , ]);
        $curl->execute();
        $curl->close();
        $this->setStatus($curl);
        if ($this->status)
        {
           return $this->getProfile($PortGenerator, null);
        }else{
            return $this->Response();
        }
    }
}