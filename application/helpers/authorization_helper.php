<?php

class AUTHORIZATION
{
    public static function validateTimestamp($token)
    {
        $CI =& get_instance();
        $token = self::validateToken($token);
        if ($token != false && (now() - $token->timestamp < ($CI->config->item('token_timeout') * 60))) {
            return $token;
        }
        return false;
    }

    public static function validateToken($token) {
        try {
            $CI                 =   & get_instance();
            $token              =   JWT::decode($token, $CI->config->item('jwt_key'));
            if(!empty($token)) {
                $now                =   intval(time());
                $token_created_at   =   intval($token->created_at);
                $token_lifetime     =   intval($token->valid_for);
                $token_live         =   $token_created_at + $token_lifetime;
                if($token_live >= $now) {
                    return $token;
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }
            
        } catch(Exception $e) {
            return FALSE;
        }
    }

    public static function generateToken($data) {
        try {
            $CI                 =   & get_instance();
            $data['created_at'] =   time();
            $data['valid_for']  =   86400*1;
            return JWT::encode($data, $CI->config->item('jwt_key'));
        } catch(Exception $e) {
            return FALSE;
        }
    }

}