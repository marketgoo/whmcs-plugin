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

require_once '../marketgooAPI/marketgooAPI.php';
require_once '../marketgooHelpers/cPanelCheckDatabase.php';
require_once '../marketgooProvisioning/marketgooProvisioning.php';

class cPanelCheck
{

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function run()
    {
        try
        {
            $username       = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_STRING);
            $domain         = filter_input(INPUT_GET, 'domain', FILTER_SANITIZE_STRING);
            $pid            = filter_input(INPUT_GET, 'pid', FILTER_SANITIZE_STRING);
            $protocolServer = filter_input(INPUT_SERVER, 'REQUEST_SCHEME', FILTER_SANITIZE_STRING);
            $serverName     = filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_STRING);
            $scriptName     = filter_input(INPUT_SERVER, 'SCRIPT_NAME', FILTER_SANITIZE_STRING);
            $whmcsUrl       = str_replace('/modules/servers/marketgoo/cPanelCheck/cPanelCheck.php', '', $scriptName);

            $procotol = strlen($protocolServer) > 0 ? $protocolServer : 'http';
            $endpoint = $procotol.'://' . $serverName.$whmcsUrl;

            $account = cPanelCheckDatabase::getAccountDetails($username, $domain);
            $server  = cPanelCheckDatabase::getServerDetails($account['server']);

            if (isset($account['username']) && strtolower($account['domainstatus']) != 'terminated')
            {
                $marketGooAPI = new MarketgooAPI($server['hostname'], $server['password']);

                $link = $marketGooAPI->get(['request' => ['login' => $account['username']], 'additional' => ['expires' => 30]]);
            }
            else
            {
                $link = cPanelCheckDatabase::generateCartLink($endpoint, $domain, $username, $pid);
            }

            header('Location: '.$link);
            exit();
        }
        catch (Exception $e)
        {
            echo "Internal Error";
            exit();
        }
    }
}

$api = new cPanelCheck($token);
$api->run();
?>
