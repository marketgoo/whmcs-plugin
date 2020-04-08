<?php

/* * ********************************************************************
 * 
 *
 *  CREATED BY MODULESGARDEN       ->        http://modulesgarden.com
 *  AUTHOR                         ->     marcin.do@modulesgarden.com
 *  CONTACT                        ->       contact@modulesgarden.com
 *
 *
 *
 * This software is furnished under a license and may be used and copied
 * only  in  accordance  with  the  terms  of such  license and with the
 * inclusion of the above copyright notice.  This software  or any other
 * copies thereof may not be provided or otherwise made available to any
 * other person.  No title to and  ownership of the  software is  hereby
 * transferred.
 *
 *
 * ******************************************************************** */

/**
 * Description of CpanelNVContainer
 *
 * @author Marcin Domanski <marcin.do@modulesgarden.com>
 * @link http://modulesgarden.com ModulesGarden - Top Quality Custom Software Development
 * @license http://www.modulesgarden.com/terms_of_service
 */
class CpanelNVContainer
{

    private $cpanel;
    private $containerKey = 'marketgoo_licenses';
    private $container    = array();
    private $changed = false;

    function __construct($cpanel)
    {
        $this->cpanel = $cpanel;
        $this->load();
    }

    private function load()
    {
        $rc = $this->cpanel->api2("NVData", "get", array("names" => $this->containerKey));
        $value = $rc['cpanelresult']['data'][0]['value'];
        if(strlen($value) > 2) {
            $this->container = unserialize($rc['cpanelresult']['data'][0]['value']);
        }
        return;
    }

    public function offsetSet($key, $value)
    {
        $this->container[$key] = $value;
        $this->changed = true;
        $this->save();
    }

    public function offsetGet($key)
    {
        return isset($this->container[$key])? $this->container[$key]:'';
    }

    private function save()
    {
        return $this->cpanel->api1("NVData", "set", array($this->containerKey, serialize($this->container)));
    }

}
