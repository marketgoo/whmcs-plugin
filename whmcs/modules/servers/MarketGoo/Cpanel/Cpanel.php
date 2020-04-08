<?php

namespace Servers\MarketGoo\Cpanel;

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

require_once ROOTDIR.'/modules/servers/MarketGoo/MarketGooHelpers/PDOWrapper.php';

/**
 * Description of Cpanel
 *
 * @author Marcin Domanski <marcin.do@modulesgarden.com>
 * @link http://modulesgarden.com ModulesGarden - Top Quality Custom Software Development
 * @license http://www.modulesgarden.com/terms_of_service
 */
class Cpanel
{

    public $serviceId;

    public $params;

    private $hosting;

    private $item;

    private $cpanelUsername;

    private $cpanelPassword;

    private $cpanelUrl;

    private $cookies;

    private $cpsess;

    private $template;

    private $domain;

    function __construct($params)
    {
        $this->params         = $params;
        $this->domain         = $this->params['customfields']['domain'];
        $this->serviceId      = (int) $this->params['serviceid'];
        $this->cpanelUsername = $this->params['customfields']['cpanel_username'];
        $this->item           = $this->params['configoptions']['producttype'];

        $this->findCpanelHosting();

        if (!empty($this->hosting))
        {
            $this->cpanelPassword = $this->findCpanelPassword();
            $this->cpanelUrl      = $this->findCpanelUrl();

            $this->connectToCpanel();
        }
        else
        {
            logActivity(
                sprintf(
                    'MarketGoo: Passed username (%s) does not match any cPanel hostings for this account. Service ID: %s',
                    $this->cpanelUsername, $this->serviceId, $this->serviceId
                )
            );
        }
    }

    private function findCpanelHosting()
    {
        $query = sprintf('SELECT hosting.*, '
                . 'server.ipaddress as server_ipaddress, '
                . 'server.username as server_username, '
                . 'server.password as server_password, '
                . 'server.secure as server_secure FROM tblhosting hosting '
                . 'LEFT JOIN tblproducts prod ON hosting.packageid = prod.id '
                . 'LEFT JOIN tblservers server ON hosting.server = server.id '
                . 'WHERE hosting.userid = %s AND prod.servertype = "cpanel" '
                . 'AND hosting.username = "%s"', $this->params['userid'], $this->cpanelUsername);

        $result = \PDOWrapper::query($query);

        if ($result == false)
        {
            return false;
        }

        $hosting = \PDOWrapper::fetch_array($result);

        $this->hosting = $hosting;
    }

    private function findCpanelPassword()
    {
        $password = $this->hosting['password'];

        return decrypt($password);
    }

    private function findCpanelUrl()
    {
        $ip       = $this->hosting['server_ipaddress'];
        $secure   = ($this->hosting['server_secure'] == 'on');
        $protocol = ($secure) ? 'https' : 'http';
        $port     = ($secure) ? '2083' : '2082';

        return sprintf('%s://%s:%s', $protocol, $ip, $port);
    }

    private function connectToCpanel()
    {
        $url = sprintf('%s/login/?user=%s&pass=%s', $this->cpanelUrl, $this->cpanelUsername, urlencode($this->cpanelPassword));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $resp = curl_exec($ch);

        if ($resp === false)
        {
            throw new \Exception('cURL Error : ' . curl_error($ch));
        }

        if(curl_error($ch))
        {
            logModuleCall('MarketGoo','Error when connecting to cPanel', ['URL' => $url], curl_error($ch));

            throw new \Exception('Error when connecting to cPanel: '.curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode >= 400 && $httpCode < 500)
        {
            logModuleCall('MarketGoo', 'Error when connecting to cPanel', ['URL' => $url], ['errorCode' => $httpCode]);

            throw new \Exception('CODE: '.$httpCode);
        }

        curl_close($ch);

        $this->setCookies($resp);
        $this->setCpSession($resp);
        $this->setTemplate($resp);

        return true;
    }

    private function setCookies($resp)
    {
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $resp, $matches);
        $cookies = [];

        foreach ($matches[1] as $item)
        {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }

        $this->cookies = $cookies;
    }

    private function setCpSession($resp)
    {
        preg_match_all('/^Location:\s*([^;]*)/mi', $resp, $matches);

        $match    = $matches[1][0];
        $exploded = explode('/', $match);

        $this->cpsess = $exploded[1];
    }

    private function setTemplate($resp)
    {
        preg_match_all('/^Location:\s*([^;]*)/mi', $resp, $matches);

        $match    = $matches[1][0];
        $exploded = explode('/', $match);

        $this->template = $exploded[3];
    }

    public function sendUuid($uuid)
    {
        if(!empty($this->hosting))
        {
            $url = sprintf(
                    '%s/%s/frontend/%s/marketgoo/index.live.php?'
                    . 'item=%s'
                    . '&domain=%s'
                    . '&signupok=%s'
                    . '&pid=%s', $this->cpanelUrl, $this->cpsess, $this->template, $this->item, $this->domain, $uuid, $this->params['pid']);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $this->addCookies($ch);
            $resp = curl_exec($ch);
            
            if(curl_error($ch))
            {
                logModuleCall('MarketGoo','SendUuidToCpanel', ['URL' => $url, 'uuid' => $uuid], curl_error($ch));

                throw new Exception('Error when connecting to cPanel: '.curl_error($ch));
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode >= 400 && $httpCode < 500)
            {
                logModuleCall('MarketGoo', 'SendUuidToCpanel', ['URL' => $url], print_r($resp, true), ['uuid' => $uuid], []);

                throw new Exception('CODE: '.$httpCode.'. '.$resp);
            }

            logModuleCall('MarketGoo', 'SendUuidToCpanel', ['URL' => $url], print_r($resp, true), ['uuid' => $uuid], []);
            
            return $resp;
        }
    }

    private function addCookies($ch)
    {
        $cookie = [];

        foreach ($this->cookies as $key => $value)
        {
            $cookie[] = "{$key}={$value}";
        }

        $cookie = implode('; ', $cookie);

        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
}
