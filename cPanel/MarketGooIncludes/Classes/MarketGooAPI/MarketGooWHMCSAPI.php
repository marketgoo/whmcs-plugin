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

class MarketGooWHMCSAPI
{
    private $MarketGooApikey;
    private $MarketGooDomain;
    private $WHMCSEndpoint;

    public function __construct($params)
    {
        if (!file_exists($params->configFile))
            throw new Exception('Configuration file not found!');
            
        $content = parse_ini_file($params->configFile);
        $this->WHMCSEndpoint = $content['endpoint'];
        $this->MarketGooApikey = $content['marketapikey'];
        $this->MarketGooDomain = $content['marketdomain'];
    }

    public function getRedirect($username, $domain)
    {
        return sprintf('%s/modules/servers/MarketGoo/cPanelCheck/cPanelCheck.php?username=%s&domain=%s',
                    $this->WHMCSEndpoint,
                    $username,
                    $domain);
    }

    public function getMarketGooApikey()
    {
        return $this->MarketGooApikey;
    }

    public function getMarketGooDomain()
    {
        return $this->MarketGooDomain;
    }
    
    public function getWHMCSEndpoint()
    {
        return $this->WHMCSEndpoint;
    }

}
