<?php 

namespace App\Api;

class json
{
    public static function _out($data, $flag = 0, int $depth = 512)
    {
        $out = json_encode($data, $flag, $depth);
        if (self::_is($out)) return $out;
        else return self::_out($out);
    }
    public static function _in($data, $associative = null, int $depth = 512, int $flags = 0)
    {
        $out = json_decode($data, $associative, $depth, $flags);
        return $out;
    }
    public static function _is($string)
    {
        if (is_numeric($string))
        {
            if (is_object($string) === false)
            {
                return false;
            }
            else
            {
                return true;
            }
        }
        elseif (is_array($string))
        {
            return false;
        }
        elseif (is_object($string))
        {
            return false;
        }
        else
        {
            json_decode($string);
            return json_last_error() === JSON_ERROR_NONE;
        }
    }
}