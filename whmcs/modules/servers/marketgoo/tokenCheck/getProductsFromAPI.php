<?php

$action    = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
$postToken = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);

if ($action == 'GetProducts' && $postToken && $postToken != '')
{
    require_once '../../../../init.php';

    if (file_exists('token.php'))
    {
        include 'token.php';

        if (!$token || $token == '')
        {
            $error = ["result" => "error", "message" => "Cannot read the token!"];
            echo json_encode($error);
        }
        elseif ($postToken !== $token)
        {
            $error = ["result" => "error", "message" => "Received token not match module token!"];
            echo json_encode($error);
        }

        $command  = 'GetProducts';
        $postData = [
            'module' => 'marketgoo'
        ];

        $results = localAPI($command, $postData);

        $resultToReturn = json_encode($results);

        echo $resultToReturn;
    }
    else
    {
        $error = ["result" => "error", "message" => "Token file not found!"];
        echo json_encode($error);
    }
}
else
{
    $error = ["result" => "error", "message" => "Token not found or unknown action!"];
    echo json_encode($error);
}
