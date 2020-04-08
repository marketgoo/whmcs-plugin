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
        'MarketGooAPI',
        'MarketGooHelpers',
        'MarketGooProvisioning'
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
        ->where('type', '=', 'MarketGoo')
        ->first();

    $serverData                   = [];
    $serverData['serverhostname'] = $serverDataRaw->hostname;
    $serverData['serverpassword'] = decrypt($serverDataRaw->password);

    //initialize MarketGoo API
    $marketGoo  = new MarketGooProvisioning($serverData);
    $products   = $marketGoo->getProductsList();
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

    $conf   = new ConfigurableOptionsGenerator($_REQUEST['id'], 'MarketGoo');
    $custom = new CustomFieldGenerator($_REQUEST['id'], 'MarketGoo');

    $conf->generate($options);
    $custom->generate($customOptions);
}

function MarketGoo_ConfigOptions($params)
{
    $customOptions = [
        ['fieldkey' => 'cpanel_username'],
        ['fieldkey' => 'domain']
    ];

    $conf   = new ConfigurableOptionsGenerator($_REQUEST['id'], 'MarketGoo');
    $custom = new CustomFieldGenerator($_REQUEST['id'], 'MarketGoo');
    
    if ($conf->checkIfAlreadyGenerated() && $custom->checkIfAlreadyGenerated($customOptions))
    {
        $configarray = [
            "username" => [
                "FriendlyName" => " ", //Generate Custom Fields and Configurable Options
                "Description"  => '<td colspan=4><div class="infobox" style="margin: 0"><strong><span class="title">Addtional Fields Already Generated</span></strong></div></td>',
            ]
        ];

        return $configarray;
    }
    else
    {
        $url = sprintf('configproducts.php?action=%s&id=%s&tab=3&gencustfield=true&success=true', 'edit', $_REQUEST['id']);

        $configarray = [
            "username" => [
                "FriendlyName" => " ", //Generate Custom Fields and Configurable Options
                "Description"  => '<a href="'.$url.'" class="btn btn-primary">Generate Addtional Fields</a>',
            ]
        ];
    }

    return $configarray;
}

function MarketGoo_CreateAccount($params)
{
    try
    {
        $marketGoo = new MarketGooProvisioning($params);
        $cpanel    = new Servers\MarketGoo\Cpanel\Cpanel($params);

        $accountId = $marketGoo->create($params);
        
        if (empty($accountId) || !$accountId || $accountId == '')
        {
            return 'Error when creating MarketGoo account!';
        }
        
        try
        {
            //create account on cPanel
            $cPanelAccount = $cpanel->sendUuid($accountId);

            if (!$cPanelAccount || $cPanelAccount == '')
            {
                $marketGoo->terminate($accountId);

                return 'Error when connecting to the cPanel!';
            }
        }
        catch (Exception $ex)
        {
            logModuleCall('MarketGoo', 'Errro when connecting to the cPanel!', $ex->getMessage(), $ex);

            $marketGoo->terminate($accountId);
            
            return "Error when connecting to the cPanel";
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
            //delete cPanel and MarketGoo
            $marketGoo->terminate($accountId);
            $cpanel->sendUuid('terminate');

            logModuleCall('MarketGoo', 'Errro when updateing WHMCS product!', $vars, $result);

            return 'Errro when updateing WHMCS product!';
        }

        if (!empty($accountId) && isset($params['configoptions']['keywords']) && $params['configoptions']['keywords'] > 0)
        {
            $marketGoo->addKeywords($accountId, $params['configoptions']['keywords']);
        }

        return 'success';
    }
    catch (Exception $e)
    {
        logModuleCall('MarketGoo', 'Errro when creating MarketGoo account!', $e->getMessage(), $e);

        return $e->getMessage();
    }
}

function MarketGoo_TerminateAccount($params)
{
    try
    {
        $marketGoo = new MarketGooProvisioning($params);
        $cpanel    = new Servers\MarketGoo\Cpanel\Cpanel($params);
        
        $marketGoo->terminate($params['username']);
        $cpanel->sendUuid('terminate');
        
        return 'success';
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }
}

function MarketGoo_SuspendAccount($params)
{
    try
    {
        $marketGoo = new MarketGooProvisioning($params);
        $cpanel    = new Servers\MarketGoo\Cpanel\Cpanel($params);

        $marketGoo->suspend($params['username']);
        $cpanel->sendUuid('terminate');

        return 'success';
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }
}

function MarketGoo_UnsuspendAccount($params)
{
    try
    {
        $marketGoo = new MarketGooProvisioning($params);
        $cpanel    = new Servers\MarketGoo\Cpanel\Cpanel($params);

        $marketGoo->unsuspend($params['username']);
        $cpanel->sendUuid($params['username']);

        return 'success';
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }
}

function MarketGoo_ServiceSingleSignOn(array $params)
{
    $return = ['success' => false];

    try
    {
        $marketGoo = new MarketGooProvisioning($params);
        $loginLink = $marketGoo->login($params['username']);

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

function MarketGoo_ClientArea($params)
{
    try
    {
        $marketGoo = new MarketGooProvisioning($params);

        if (isset($_POST['uid']) && !empty($_POST['uid']) && $_POST['uid'] == $params['userid'])
        {
            $loginLink = $marketGoo->login($params['username']);

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

function MarketGoo_ChangePackage($params)
{
    try
    {
        $marketGoo = new MarketGooProvisioning($params);

        if (isset($params['configoptions']['producttype']))
        {
            $marketGoo->changeProduct($params['username'], $params['configoptions']['producttype']);
        }

        if (isset($params['configoptions']['keywords']))
        {
            $marketGoo->updateAddon($params['username'], $params['configoptions']['keywords']);
        }

        return "success";
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }
}
