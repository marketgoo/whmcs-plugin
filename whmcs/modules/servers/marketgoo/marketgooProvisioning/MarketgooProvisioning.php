<?php

require_once __DIR__ . '/../marketgooAPI/MarketgooAPI.php';

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

    private $marketgooAPI;

    public function __construct($params)
    {
        $this->marketgooAPI = new MarketgooAPI(
            $params['serverhostname'],
            $params['serverpassword'],
            isset($params['whmcsVersion']) ? $params['whmcsVersion'] : null
        );
    }

    public function create($params)
    {
        $domain = isset($_SESSION['marketgoo']['domain']) ? $_SESSION['marketgoo']['domain'] : $params['customfields']['Domain'];

        $additional = [];
        if (!in_array($params['configoption2'], ['none', 'select'])) {
            $additional['partner_token'] = $params['configoption2'];
        }

        if (!empty($params['configoption3'])) {
            $additional['promo'] = $params['configoption3'];
        }

        $response = $this->marketgooAPI->post(
            'accounts',
            array_merge([
                'product' => $params['configoption1'],
                'domain'  => $domain,
                'name'    => $params['clientsdetails']['fullname'],
                'email'   => $params['clientsdetails']['email'],
                'lang'    => $this->getLanguageCode($params['clientsdetails']['language']),
            ], $additional)
        );

        return $response->uuid;
    }

    /** OK **/
    public function terminate($accountId)
    {
        return $this->marketgooAPI->delete(sprintf('accounts/%s', $accountId));
    }

    /** OK **/
    public function suspend($accountId)
    {
        return $this->marketgooAPI->put(sprintf('accounts/%s/suspend', $accountId));
    }

    /** OK **/
    public function unsuspend($accountId)
    {
        return $this->marketgooAPI->put(sprintf('accounts/%s/resume', $accountId));
    }

    /** OK **/
    public function login($accountId)
    {
        $result = $this->marketgooAPI->get(sprintf('accounts/%s/login?expires=30', $accountId));
        return $result->meta->public_login_url;
    }

    /** OK **/
    public function changeProduct($accountId, $newProduct)
    {
        return $this->marketgooAPI->put(sprintf('accounts/%s/upgrade', $accountId), ['product' => $newProduct, 'force' => true]);
    }

    /** OK **/
    public function getProductsList()
    {
        return $this->marketgooAPI->get("me/products");
    }

    /** OK **/
    public function getParnerTokens()
    {
        return $this->marketgooAPI->get("me/tokens");
    }

    protected function getLanguageCode($language = '')
    {
        // get system default language if language is empty
        if (empty($language)) {
            $language = WHMCS\Config\Setting::getValue("Language");
        }

        $language = strtolower($language);
        $langCodes = [
            'english' => 'en',
            'swedish' => 'sv',
            'danish' => 'da',
            'spanish' => 'es',
            'french' => 'fr',
            'portuguese' => 'pt',
            'german' => 'de',
            'dutch' => 'nl',
            'norwegian' => 'nb'
        ];

        return $langCodes[$language] ?? $langCodes['english'];
    }

}
