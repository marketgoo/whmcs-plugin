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

if (isset($_REQUEST['gencustfield']) && $_REQUEST['gencustfield'] == 'true')
{
    //make  a request to marketgoo api to get the available product types
    $serverDataRaw = Capsule::table('tblservers')
        ->select('hostname', 'password')
        ->where('type', '=', 'marketgoo')
        ->first();

    $serverData                   = [];
    $serverData['serverhostname'] = $serverDataRaw->hostname;
    $serverData['serverpassword'] = decrypt($serverDataRaw->password);

    //initialize marketgoo API
    $marketgoo  = new MarketgooProvisioning($serverData);
    $products   = $marketgoo->getProductsList();
    $suboptions = [];

    foreach ($products as $product)
    {
        $option = $product['key'].'|'.$product['name'];
        array_push($suboptions, $option);
    }

    $options = [
        [
            'optionname'  => 'keywords',
            'displayname' => 'Additional Keywords',
            'optiontype'  => 1,
            'qtyminimum'  => 0,
            'qtymaximum'  => 0,
            'suboptions'  => [
                'none|None',
                'keyword10|Additional 10 Keywords',
                'keyword30|Additional 30 Keywords',
            ]
        ],
        [
            'optionname'  => 'producttype',
            'displayname' => 'Product Type',
            'optiontype'  => 1,
            'qtyminimum'  => 0,
            'qtymaximum'  => 0,
            'suboptions'  => $suboptions
        ]
    ];

    $customOptions = [
        [
            'fieldkey'    => 'cpanel_username',
            'fieldname'   => 'cPanel Username',
            'description' => 'Please enter your cPanel Username',
            'required'    => 'off',
            'showorder'   => 'off',
            'showinvoice' => 'off',
            'confName'    => '',
            'description' => '',
            'sortorder'   => 0
        ],
        [
            'fieldkey'    => 'domain',
            'fieldname'   => 'Domain',
            'description' => 'Please enter Domain you want to protect',
            'required'    => 'off',
            'showorder'   => 'off',
            'showinvoice' => 'off',
            'confName'    => '',
            'description' => '',
            'sortorder'   => 1
        ]
    ];

    $conf   = new ConfigurableOptionsGenerator($_REQUEST['id'], 'marketgoo');
    $custom = new CustomFieldGenerator($_REQUEST['id'], 'marketgoo');

    $conf->generate($options);
    $custom->generate($customOptions);
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
    logModuleCall('marketgoo', 'ConfigOptions', 'request', 'response', $options, '');
    return [
        "product" => [
            "FriendlyName" => "Product",
            "Type" => "radio",
            "Options" => $options,
            "Description" => "Choose the marketgoo Product",
        ],
    ];
}

function marketgoo_CreateAccount($params)
{
    try
    {
        $marketgoo = new MarketgooProvisioning($params);
        $cpanel    = new Servers\Marketgoo\Cpanel\Cpanel($params);

        $accountId = $marketgoo->create($params);
        
        if (empty($accountId) || !$accountId || $accountId == '')
        {
            return 'Error when creating marketgoo account!';
        }
        
        try
        {
            //create account on cPanel
            $cPanelAccount = $cpanel->sendUuid($accountId);

            if (!$cPanelAccount || $cPanelAccount == '')
            {
                $marketgoo->terminate($accountId);

                return 'Error connecting to cPanel!';
            }
        }
        catch (Exception $ex)
        {
            logModuleCall('marketgoo', 'Error connecting to cPanel!', $ex->getMessage(), $ex);

            $marketgoo->terminate($accountId);
            
            return "Error connecting to cPanel";
        }

        $vars = [
            'serviceid'       => $params['serviceid'],
            'serviceusername' => $accountId,
            'servicepassword' => ' '
        ];

        $result = localAPI('UpdateClientProduct', $vars);
        
        //check if WHMCS api error
        if ($result['result'] != 'success')
        {
            //delete cPanel and marketgoo
            $marketgoo->terminate($accountId);
            $cpanel->sendUuid('terminate');

            logModuleCall('marketgoo', 'Error when updating WHMCS product!', $vars, $result);

            return 'Error when updating WHMCS product!';
        }

        if (!empty($accountId) && isset($params['configoptions']['keywords']) && $params['configoptions']['keywords'] > 0)
        {
            $marketgoo->addKeywords($accountId, $params['configoptions']['keywords']);
        }
        logModuleCall('marketgoo', 'CreateAccount', $params, 'response', $accountId, '');

        return 'success';
    }
    catch (Exception $e)
    {
        logModuleCall('marketgoo', 'Error when creating marketgoo account!', $e->getMessage(), $e);

        return $e->getMessage();
    }
}

function marketgoo_TerminateAccount($params)
{
    try
    {
        $marketgoo = new MarketgooProvisioning($params);
        $cpanel    = new Servers\Marketgoo\Cpanel\Cpanel($params);
        
        $marketgoo->terminate($params['username']);
        $cpanel->sendUuid('terminate');
        
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
        $cpanel    = new Servers\Marketgoo\Cpanel\Cpanel($params);

        $marketgoo->suspend($params['username']);
        $cpanel->sendUuid('terminate');

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
        $cpanel    = new Servers\Marketgoo\Cpanel\Cpanel($params);

        $marketgoo->unsuspend($params['username']);
        $cpanel->sendUuid($params['username']);

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
        $loginLink = $marketgoo->login($params['username']);

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

        if (isset($_POST['uid']) && !empty($_POST['uid']) && $_POST['uid'] == $params['userid'])
        {
            $loginLink = $marketgoo->login($params['username']);

            header('Location: ' . $loginLink);
            die();
        }

        return [
            'templatefile' => 'clientarea',
            'vars'         => ['uid' => $params['userid']]
        ];
    }
    catch (Exception $e)
    {
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
