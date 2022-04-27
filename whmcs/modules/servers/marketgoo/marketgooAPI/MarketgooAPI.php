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
    private $whmcsVersion;

    public function __construct($endpoint, $token, $whmcsVersion = null)
    {
        $this->endpoint = $endpoint;
        $this->token = $token;
        $this->whmcsVersion = $whmcsVersion;
    }

    public function __call($method, $params)
    {
        return $this->request($method, $params[0], $params[1] ?? null);
    }

    private function request($method, $endpoint, $data = [])
    {
        $ch = curl_init();
        $url = 'https://' . $this->endpoint . '/api/' . $endpoint;
        $curlParams = [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                "X-Auth-Token: ".$this->token,
                "Accept: application/vnd.marketgoo.api+json",
                "User-Agent: mktgoo-whmcs/2.0"
            ],
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ];

        if (!is_null($this->whmcsVersion)) {
            $curlParams[CURLOPT_HTTPHEADER][] = "X-WHMCS-Version: " . $this->whmcsVersion;
        }

        if (in_array(strtolower($method), ["post", "put", "patch"]))
        {
            $curlParams[CURLOPT_POST] = true;
            $curlParams[CURLOPT_HTTPHEADER][] = "Content-Type: application/vnd.marketgoo.api+json";
            $curlParams[CURLOPT_POSTFIELDS] = json_encode($data);
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
            throw new Exception('CODE: ' . $this->decode_error(json_decode($result)));
        }

        return $this->decode_jsonapi(json_decode($result));
    }

    private function decode_error($data)
    {
        if (isset($data->errors)) {
            return implode(", ", array_reduce($data->errors, function ($carry, $item) {
                $carry[] = (isset($item->status) ? $item->status : $item->code) . ": " . $item->title . " - " . $item->detail;
                return $carry;
            }, array()));
        } else {
            return "Unable to decode error string...";
        }
    }

    private function decode_jsonapi($data)
    {
        // root value contains "data" field
        if (isset($data->data)) {
            if (is_array($data->data)) {
                return array_map(function ($item) {
                    return $this->decode_jsonapi($item);
                }, $data->data);
            } else {
                return $this->decode_jsonapi($data->data);
            }
        }

        // Or, it can be a regular object with "attributes"
        if (isset($data->attributes)) {
            $newobj = $data->attributes;
            $newobj->id = $data->id;
            return $newobj;
        }

        // By default, just return the same object
        return $data;
    }
}
