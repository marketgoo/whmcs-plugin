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

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'init.php';
require 'MktgooPDOWrapper.php';

class cPanelCheckDatabase
{
    public static function getAccountDetails($username, $domain)
    {
        $userId = MktgooPDOWrapper::real_escape_string(self::getUserId($username, $domain));
        $id = MktgooPDOWrapper::real_escape_string(self::getMarketgooServer());
        $result = MktgooPDOWrapper::query("SELECT username, server, domainstatus FROM tblhosting WHERE server ='" . $id . "' AND userid='" . $userId . "' LIMIT 1");
        return MktgooPDOWrapper::fetch_assoc($result);
    }

    private static function getUserId($username, $domain)
    {
        $username = MktgooPDOWrapper::real_escape_string($username);
        $domain = MktgooPDOWrapper::real_escape_string($domain);
        $result = MktgooPDOWrapper::query("SELECT userid FROM tblhosting RIGHT JOIN tblcustomfieldsvalues ON tblcustomfieldsvalues.relid=tblhosting.id WHERE tblcustomfieldsvalues.value='" . $username . "' OR tblcustomfieldsvalues.value='" . $domain . "'");
        $return = MktgooPDOWrapper::fetch_assoc($result);
        return isset($return['userid']) ? $return['userid'] : false;
    }

    private static function getMarketgooServer()
    {
        $result = MktgooPDOWrapper::query("SELECT id FROM tblservers WHERE type='marketgoo' LIMIT 1");
        $id = MktgooPDOWrapper::fetch_assoc($result);
        return $id['id'];
    }

    public static function getServerDetails($id)
    {
        $result = MktgooPDOWrapper::query("SELECT hostname, password FROM tblservers WHERE id='" . MktgooPDOWrapper::real_escape_string($id) . "' LIMIT 1");
        $data = MktgooPDOWrapper::fetch_assoc($result);
        $data['password'] = decrypt($data['password']);
        return $data;
    }

    public static function generateCartLink($endpoint, $domain, $username, $pid)
    {
        if (!isset($pid)) {
            $_SESSION['marketgoo'] = array(
                'username' => $username,
                'domain' => $domain,
            );
            logModuleCall('marketgoo', 'generateCartLink', $endpoint, $username, $_SESSION['marketgoo']);

            return $endpoint . '/cart.php';
        }

        $cpanelHosting = self::findCpanelServer($username);

        $_SESSION['marketgoo'] = array(
            'username' => $username,
            'domain' => $domain,
            'product_id' => $pid,
            'customfields' => array(
                'username' => self::getCustomFieldID($pid, 'cpanel_username'),
                'domain' => self::getCustomFieldID($pid, 'domain')
            )
        );
        logModuleCall('marketgoo', 'generateCartLink', $endpoint, $cPanelHosting['user_id'], $cPanelHosting);

        return $endpoint . '/cart.php?a=add&pid=' . $pid;
    }

    private static function getCustomFieldID($productId, $name = 'cPanel Username')
    {
        $pid = MktgooPDOWrapper::real_escape_string($productId);
        $name = MktgooPDOWrapper::real_escape_string($name);
        $q = 'SELECT id FROM tblcustomfields WHERE relid = ' . $pid . ' AND fieldname LIKE "' . $name . '|%" LIMIT 1';
        $result = MktgooPDOWrapper::query($q);
        $return = MktgooPDOWrapper::fetch_assoc($result);
        return $return['id'];
    }

    public static function findCpanelServer($username)
    {
        $query = sprintf(
            'SELECT hosting.*, '
                . 'hosting.id as hosting_id, '
                . 'hosting.userid as user_id, '
                . 'server.ipaddress as server_ipaddress, '
                . 'server.username as server_username, '
                . 'server.password as server_password, '
                . 'server.secure as server_secure FROM tblhosting hosting '
                . 'LEFT JOIN tblservers server ON hosting.server = server.id '
                . 'WHERE hosting.username = "%s" AND server.type = "cpanel" ',
            $username
        );

        $result = MktgooPDOWrapper::query($query);
        if ($result == false)
            return false;

        return MktgooPDOWrapper::fetch_array($result);
    }
}
