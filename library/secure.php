<?php

//rely on: system session http
class secure{

    //延伸的md5方法
    static function md5($string){
        return md5($string . system::config('system.secure.key'));
    }

    //返回令牌(user_agent,ip)
    static function token($string = '', $ip = ''){
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        return self::md5($agent . $ip . $string);
    }

    //特殊字符过滤
    static function symbol($string, $is_strict = false){
        $risk = '~^<>`\'"\\';
        $is_strict and $risk .= '@!#$%&?+-*/={}[]()|,.:;';
        $risk = str_split($risk, 1);
        return str_replace($risk, '', $string);
    }

    //正则匹配
    static function match($string, $regexp){
        $regexp_list = basic::meta('regexp');
        if (isset($regexp_list[$regexp])) {
            $regexp = $regexp_list[$regexp];
        }
        return preg_match($regexp, $string);
    }

    //正则过滤
    static function filter($value, $regexp, $default = ''){
        return self::match($value, $regexp) ? $value : $default;
    }

    //获取6位密码保护串
    static function salt(){
        $symbol = '!@#$%&?~^<>`+-*/={}[]()|_,.:;';
        $char = md5(time() . rand(100, 999));
        $salt = $char[rand(0, 31)] . $symbol[rand(0, 28)] . $char[rand(0, 31)];
        return $salt . $char[rand(0, 31)] . $symbol[rand(0, 28)] . $char[rand(0, 31)];
    }

    //获取加盐加密后的密码
    static function password($password, $salt){
        return md5(md5($password) . $salt);
    }

    //加密
    static function encrypt($string, $key = 'b335a4503870a1d1'){
        $j = 0;
        $key = md5($key);
        $buffer = $data = '';
        $length = strlen($string);
        for ($i = 0; $i < $length; $i++) {
            if ($j == 32) {
                $j = 0;
            }
            $buffer .= $key[$j];
            $j++;
        }
        for ($i = 0; $i < $length; $i++) {
            $data .= $string[$i] ^ $buffer[$i];
        }
        return base64_encode($data);
    }

    //解密
    static function decrypt($string, $key = 'b335a4503870a1d1'){
        $string = base64_decode($string);

        $j = 0;
        $key = md5($key);
        $buffer = $data = '';
        $length = strlen($string);
        for ($i = 0; $i < $length; $i++) {
            if ($j == 32) {
                $j = 0;
            }
            $buffer .= substr($key, $j, 1);
            $j++;
        }
        for ($i = 0; $i < $length; $i++) {
            $data .= $string[$i] ^ $buffer[$i];
        }
        return $data;
    }

    //csrf攻击
    static function csrf($mode, $csrf = ''){
        $key = system::config('system.secure.csrf_name');
        if ($mode === 'get') {
            return session::get($key);
        }
        if ($mode === 'check') {
            //Notice 是否只用一次并清掉cookie
            //http::cookie($key,$value,true);
            return ($csrf && $csrf === session::get($key));
        }
        if ($mode === 'set') {
            $value = substr(self::token(time()), 5, 8);
            session::set($key, $value);
            http::cookie($key, $value, 7200);
            return $value;
        }
    }

    //xss检测（check）、过滤（filter）
    static function xss($string, $mode = 'check'){
        $regexp_list = basic::meta('xss');
        if ($mode === 'check') {
            $risk = 0;
            foreach ($regexp_list as $regexp) {
                if (preg_match($regexp, $string)) {
                    $risk++;
                }
            }
            return $risk;
        }
        return preg_replace($regexp_list, '', $string);
    }


}