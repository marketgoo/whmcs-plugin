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
 * Description of marketgoo
 *
 * @author Marcin Domanski <marcin.do@modulesgarden.com>
 * @link http://modulesgarden.com ModulesGarden - Top Quality Custom Software Development
 * @license http://www.modulesgarden.com/terms_of_service
 */
if (!defined("WHMCS"))
{
    die("This file cannot be accessed directly");
}

use Illuminate\Database\Capsule\Manager as Capsule;

add_hook('AfterShoppingCartCheckout', 1, 'hydrateOrderFromSessionForMarketgoo');

function hydrateOrderFromSessionForMarketgoo($vars = [])
{
    $marketGooSession = $_SESSION['marketgoo'];
    $hosting          = Capsule::table('tblhosting')
        ->where('orderid', '=', $vars['OrderID'])
        ->first();

    if(isset($marketGooSession[$hosting->userid]) && $marketGooSession[$hosting->userid]['product_id'] == $hosting->packageid)
    {
        $hostingSession = $marketGooSession[$hosting->userid];
        $username       = $hostingSession['username'];
        $domain         = $hostingSession['domain'];

        Capsule::table('tblcustomfieldsvalues')
            ->where('fieldid', '=', $hostingSession['customfields']['username'])
            ->where('relid', '=', $hosting->id)
            ->update(['value' => $username]);

        Capsule::table('tblcustomfieldsvalues')
            ->where('fieldid', '=', $hostingSession['customfields']['domain'])
            ->where('relid', '=', $hosting->id)
            ->update(['value' => $domain]);

        unset($_SESSION['marketgoo'][$hosting->userid]);
    }
}
