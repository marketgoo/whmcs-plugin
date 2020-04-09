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

class MarketgooProvisioning
{

    private $marketGooAPI;

    public function __construct($params)
    {
        $this->marketGooAPI = new MarketgooAPI($params['serverhostname'], $params['serverpassword']);
    }

    public function create($params)
    {
        $response = $this->marketGooAPI->post([
            'request'     => ['accounts' => ''],
            'additional'  => [
                'product' => $params['configoptions']['producttype'],
                'domain'  => $params['customfields']['domain'],
                'name'    => $params['clientsdetails']['fullname'],
                'email'   => $params['clientsdetails']['email']
            ]
        ]);

        return $response;
    }

    public function addKeywords($accountId, $keywordPackets)
    {
        for ($i = 0; $i < $keywordPackets; $i++)
        {
            $this->marketGooAPI->post([
                'request'    => ['accounts' => $accountId, 'addons' => ''],
                'additional' => ['addon' => 'keyword10']
            ]);
        }
    }

    public function terminate($accountId)
    {
        $this->marketGooAPI->delete(['request' => ['accounts' => $accountId]]);
    }

    public function suspend($accountId)
    {
        $this->marketGooAPI->put(['request' => ['accounts' => $accountId, 'suspend' => '']]);
    }

    public function unsuspend($accountId)
    {
        $this->marketGooAPI->put(['request' => ['accounts' => $accountId, 'resume' => '']]);
    }

    public function login($accountId)
    {
        return $this->marketGooAPI->get(['request' => ['login' => $accountId], 'additional' => ['expires' => 30]]);
    }

    public function changeProduct($accountId, $newProduct)
    {
        return $this->marketGooAPI->put([
            'request'    => ['accounts' => $accountId, 'upgrade' => ''],
            'additional' => ['product' => $newProduct]
        ]);
    }

    public function updateAddon($accountId, $newAddon)
    {
        $addons = $this->marketGooAPI->get(['request' => ['accounts' => $accountId, 'addons' => '']]);
        $addonsArray = json_decode($addons, true);

        foreach ($addonsArray as $addon)
        {
            $this->marketGooAPI->delete(['request' => ['accounts' => $accountId, 'addons' => $addon['uuid']]]);
        }

        if ($newAddon != 'none')
        {
            $this->marketGooAPI->post([
                'request'    => ['accounts' => $accountId, 'addons' => ''],
                'additional' => ['addon' => $newAddon]
            ]);
        }
    }
    
    public function getProductsList()
    {
        $products = $this->marketGooAPI->get(['request' => ['me' => 'products']]);
        
        return json_decode($products, true);
    }
}
