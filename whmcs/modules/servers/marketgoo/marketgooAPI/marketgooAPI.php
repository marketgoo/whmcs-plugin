<?php

/*
 * * ******************************************************************
 *
 *   CREATED BY MODULESGARDEN       ->        http://modulesgarden.com
 *   AUTHOR                         ->     michal.lu@modulesgarden.com
 *   CONTACT                        ->       contact@modulesgarden.com
 *
 *  This software is furnished under a license and may be used and copied
 *  only  in  accordance  with  the  terms  of such  license and with the
 *  inclusion of the above copyright notice.  This software  or any other
 *  copies thereof may not be provided or otherwise made available to any
 *  other person.  No title to and  ownership of the  software is  hereby
 *  transferred.
 *
 * * ******************************************************************
 */

class MarketgooAPI
{

    private $endpoint;
    private $token;

    public function __construct($endpoint, $token)
    {
        $this->endpoint = $endpoint;
        $this->token    = $token;
    }

    public function __call($method, $params)
    {
        $requestData = [];
        $additional  = [];

        if (isset($params[0]['request']))
        {
            $requestData = $params[0]['request'];
        }

        if (isset($params[0]['additional']))
        {
            $additional = $params[0]['additional'];
        }

        return $this->request($method, $requestData, $additional);
    }

    private function request($method, $params = [], $additional = [])
    {
        $ch         = curl_init();
        $url        = 'https://' . $this->endpoint . '/api' . $this->buildQuery($method, $params, $additional);
        $curlParams = [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => [
                "X-Auth-Token: ".$this->token,
                "Content-Type: application/x-www-form-urlencoded",
                "Accept: */*"
            ],
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ];

        if(strtolower($method) == 'post' || strtolower($method) == 'put')
        {
            $curlParams[CURLOPT_POST]       = true;
            $curlParams[CURLOPT_POSTFIELDS] = http_build_query($additional);
        }

        curl_setopt_array($ch, $curlParams);

        $result = curl_exec($ch);

        if(curl_error($ch))
        {
            logModuleCall('marketgoo',$method, ['URL' => $url, 'CURL' => $curlParams], curl_error($ch));
        }
        else
        {
            logModuleCall('marketgoo',$method, ['URL' => $url, 'CURL' => $curlParams], print_r($result, true));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode >= 400 && $httpCode < 500)
        {
            throw new Exception('CODE: '.$httpCode.'. '.$result);
        }

        return $result;
    }

    private function buildQuery($method, $params, $additional)
    {
        $string = '';

        foreach ($params as $key => $value)
        {
            $string .= '/'.$key.'/'.$value;
        }

        if (!empty($additional) && strtolower($method) == 'get')
        {
            $string .= '?'.http_build_query($additional);
        }

        return rtrim($string, '/');
    }

}
