<?php

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
		$postData = [
			'module' => 'marketgoo'
		];

		$results = localAPI($action, $postData);

		$resultToReturn = json_encode($results);

		echo $resultToReturn;
	}
	elseif ($action == 'GetClientsProducts')
	{
		$postData = [
			'module' => 'marketgoo'
		];

		$results = localAPI($action, $postData);

		$resultToReturn = json_encode($results);

		echo $resultToReturn;
	}
	else
	{
		$error = ["result" => "error", "message" => "Unknown action"];
		echo json_encode($error);
	}
}

generateResponse();

