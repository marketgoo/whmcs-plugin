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

require __DIR__ . '/Cpanel/Cpanel.php';

use WHMCS\Database\Capsule;

spl_autoload_register(function ($className)
{
    $moduleDirectories = [
        'marketgooAPI',
        'marketgooHelpers',
        'marketgooProvisioning'
    ];

    foreach ($moduleDirectories as $dir)
    {
        if (file_exists(__DIR__.DS.$dir.DS.$className.'.php'))
        {
            require __DIR__.DS.$dir.DS.$className.'.php';
            break;
        }
    }
});

if (!defined('DS'))
{
    define('DS', DIRECTORY_SEPARATOR);
}

function marketgoo_ConfigOptions($params)
{
    //make  a request to marketgoo api to get the available product types
    $serverDataRaw = Capsule::table('tblservers')
        ->select('hostname', 'password')
        ->where('type', '=', 'marketgoo')
        ->first();

    $serverData = [
        'serverhostname' => $serverDataRaw->hostname,
        'serverpassword' => decrypt($serverDataRaw->password),
    ];

    //initialize marketgoo API
    $marketgoo  = new MarketgooProvisioning($serverData);
    $products   = $marketgoo->getProductsList();
    $options = [];
    foreach ($products as $product)
    {
        $options[$product['key']] = $product['name'];
    }
    logModuleCall('marketgoo', 'ConfigOptions', $params, 'response', $options);
    return [
        "product" => [
            "FriendlyName" => "Product",
            "Type" => "dropdown",
            "Options" => $options,
            "Description" => "Choose the marketgoo Product",
        ],
    ];
}

function marketgoo_CreateAccount($params)
{
    logModuleCall('marketgoo', 'CreateAccount', $params, 'response', $params);
    try
    {
        $marketgoo = new MarketgooProvisioning($params);

        $accountId = $marketgoo->create($params);
        
        if (empty($accountId) || !$accountId || $accountId == '')
        {
            $message = 'Error when creating marketgoo account';
            logModuleCall('marketgoo', 'CreateAccount', $params, $message, $message);
            return $message;
        }

        $vars = [
            'serviceid'       => $params['serviceid'],
            'serviceusername' => $_SESSION['marketgoo']['username'],
            'domain'          => $_SESSION['marketgoo']['domain'],
			'servicepassword' => $accountId,
        ];
        $result = localAPI('UpdateClientProduct', $vars);
        
        //check if WHMCS api error
        if ($result['result'] != 'success')
        {
            //delete cPanel and marketgoo
            $marketgoo->terminate($accountId);
            $message = 'Error when updating WHMCS product';
            logModuleCall('marketgoo', 'CreateAccount', $vars, $message, $result);
            return $message;
        }
        logModuleCall('marketgoo', 'CreateAccount', $params, 'success', $accountId);
        return 'success';
    }
    catch (Exception $e)
    {
        logModuleCall('marketgoo', 'CreateAccount', $params, $e->getMessage(), $e);
        return $e->getMessage();
    }
}

function marketgoo_TerminateAccount($params)
{
    try
    {
        $marketgoo = new MarketgooProvisioning($params);
        
        $marketgoo->terminate($params['username']);
        
        return 'success';
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }
}

function marketgoo_SuspendAccount($params)
{
    try
    {
        $marketgoo = new MarketgooProvisioning($params);

        $marketgoo->suspend($params['username']);

        return 'success';
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }
}

function marketgoo_UnsuspendAccount($params)
{
    try
    {
        $marketgoo = new MarketgooProvisioning($params);

        $marketgoo->unsuspend($params['username']);

        return 'success';
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }
}

function marketgoo_ServiceSingleSignOn(array $params)
{
    $return = ['success' => false];

    try
    {
        $marketgoo = new MarketgooProvisioning($params);
        $loginLink = $marketgoo->login($params['password']);

        $return = [
            'success'    => true,
            'redirectTo' => $loginLink,
        ];
    }
    catch (Exception $e)
    {
        $return['errorMsg'] = $e->getMessage();
    }

    return $return;
}

function marketgoo_ClientArea($params)
{
    try
    {
        $marketgoo = new MarketgooProvisioning($params);
        $loginLink = $marketgoo->login($params['password']);

        logModuleCall('marketgoo', 'ClientArea', $params, $loginLink, $loginLink);

        return [
            'templatefile' => 'clientarea',
            'vars'         => [
                'target' => $loginLink,
            ]
        ];
    }
    catch (Exception $e)
    {
        logModuleCall('marketgoo', 'ClientArea', $params, $e->getMessage(), $e);
        return $e->getMessage();
    }
}

function marketgoo_ChangePackage($params)
{
    try
    {
        $marketgoo = new MarketgooProvisioning($params);

        if (isset($params['configoptions']['producttype']))
        {
            $marketgoo->changeProduct($params['username'], $params['configoptions']['producttype']);
        }

        if (isset($params['configoptions']['keywords']))
        {
            $marketgoo->updateAddon($params['username'], $params['configoptions']['keywords']);
        }

        return "success";
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }
}
