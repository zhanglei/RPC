<?php

if (!function_exists('get_real_ip')) {
    function get_real_ip() {
        $proxy_headers = array(
            'CLIENT_IP',
            'FORWARDED',
            'FORWARDED_FOR',
            'FORWARDED_FOR_IP',
            'HTTP_CLIENT_IP',
            'HTTP_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED_FOR_IP',
            'HTTP_PC_REMOTE_ADDR',
            'HTTP_PROXY_CONNECTION',
            'HTTP_VIA',
            'HTTP_X_FORWARDED',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED_FOR_IP',
            'HTTP_X_IMFORWARDS',
            'HTTP_XROXY_CONNECTION',
            'VIA',
            'X_FORWARDED',
            'X_FORWARDED_FOR'
        );

        foreach($proxy_headers as $proxy_header)
        {
            if(isset($_SERVER[$proxy_header]) && preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $_SERVER[$proxy_header])) /* HEADER ist gesetzt und dies ist eine gültige IP */
            {
                return $_SERVER[$proxy_header];
            }
            else if(isset($_SERVER[$proxy_header]) && stristr(',', $_SERVER[$proxy_header]) !== FALSE) /* Behandle mehrere IPs in einer Anfrage(z.B.: X-Forwarded-For: client1, proxy1, proxy2) */
            {
                $proxy_header_temp = trim(array_shift(explode(',', $_SERVER[$proxy_header]))); /* Teile in einzelne IPs, gib die letzte zurück und entferne Leerzeichen */

                if(($pos_temp = stripos($proxy_header_temp, ':')) !== FALSE) $proxy_header_temp = substr($proxy_header_temp, 0, $pos_temp); /* Entferne den Port */

                if(preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $proxy_header_temp)) return $proxy_header_temp;
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }
}