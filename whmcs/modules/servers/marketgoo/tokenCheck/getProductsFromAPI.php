<?php

require_once '../marketgooProvisioning/MarketgooProvisioning.php';
require_once '../marketgooHelpers/cPanelCheckDatabase.php';

function generateResponse()
{
    $postToken = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);
    if (!$postToken || $postToken == '')
    {
        $error = ["result" => "error", "message" => "Token not found"];
        echo json_encode($error);
        return;
    }
    require_once '../../../../init.php';

    if (!file_exists('token.php'))
    {
        $error = ["result" => "error", "message" => "Token file not found!"];
        echo json_encode($error);
        return;
    }
    include 'token.php';

    if (!$token || $token == '')
    {
        $error = ["result" => "error", "message" => "Cannot read the token!"];
        echo json_encode($error);
        return;
    }
    elseif ($postToken !== $token)
    {
        $error = ["result" => "error", "message" => "Received token not match module token!"];
        echo json_encode($error);
        return;
    }
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    if ($action == 'GetProducts')
    {
        $products = getProducts();

        logModuleCall('marketgoo', $action, $postData, 'response', $products);

        echo json_encode($products);
    }
    elseif ($action == 'GetClientsProducts')
    {
        $domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_STRING);
        $postData = [
            'domain' => $domain,
        ];
        $clientProducts = localAPI($action, $postData);
        if ($clientProducts['result'] != 'success')
        {
            logModuleCall('marketgoo', $action, $postData, 'response', $clientProducts);
            echo json_encode($clientProducts);
            return;
        }
        $products = getProducts();
        if ($products['result'] != 'success')
        {
            logModuleCall('marketgoo', $action, $postData, 'response', $products);
            echo json_encode($products);
            return;
        }
        $dict = [];
        foreach ($products['products']['product'] as $product)
        {
            $dict[$product['pid']] = $product;
        }

        $filtered = [];
        foreach ($clientProducts['products']['product'] as $product)
        {
            if (isset($dict[$product['pid']]))
            {
                $product['login'] = getLoginLink($product);
                $filtered[] = $product;
            }
        }
        $result = [
            'result' => 'success',
            'products' => $filtered,
        ];
        logModuleCall('marketgoo', $action, $postData, 'response', $result);

        echo json_encode($result);
    }
    else
    {
        $error = ["result" => "error", "message" => "Unknown action"];
        echo json_encode($error);
    }
}

function getProducts()
{
    $postData = [
        'module' => 'marketgoo'
    ];
    return localAPI('GetProducts', $postData);
}

function getLoginLink($product)
{
    $server = cPanelCheckDatabase::getServerDetails($product['serverid']);
    $marketgoo = new MarketgooProvisioning([
        'serverhostname' => $server['hostname'],
        'serverpassword' => $server['password'],
    ]);
    return $marketgoo->login($product['password']);
}

generateResponse();

