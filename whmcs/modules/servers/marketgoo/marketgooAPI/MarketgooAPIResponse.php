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

class MarketgooAPIResponse
{

    public $raw;
    
    public function __construct($curlResponse)
    {
        $this->raw = $curlResponse;
        $array = json_decode($this->raw, true);
        if (json_last_error() == JSON_ERROR_NONE && is_array($array)) {
            array_map(function($key, $value) {
                $this->{$key} = $value;
            }, $array);
        }
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        } else {
            return false;
        }
    }

}
