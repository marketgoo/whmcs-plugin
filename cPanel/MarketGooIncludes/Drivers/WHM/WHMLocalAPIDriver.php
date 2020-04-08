<?php

class WHMLocalAPIDriver extends AbstractLocalAPIDriver
{

    protected $apiKey;
    private $sortCol = 'domain';
    private $sortVect = 'ASC';
    public $currentUsername = false;
    public $currentDomain = false;
    public $lastParams = array();
    public $typeList = array('base', 'addon', 'parked');

    function __construct($params)
    {
        parent::__construct($params);
    }

    public function _connect($params)
    {
        $accesKeyFile = '/root/.accesshash';

        if (!file_exists($accesKeyFile)) {
            throw new Exception("Cant find Acces Hash File");
        }

        $handle = fopen($accesKeyFile, 'r');
        $this->apiKey = preg_replace("'(\r|\n)'", "", fread($handle, 1024));

        if (empty($this->apiKey)) {
            throw new Exception("Cant find Acces Hash File");
        }
    }

    private function _request($function, array $params = array())
    {
        $ch = curl_init();

        $this->lastParams = $params;

        curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:2086/json-api/" . $function . '?' . http_build_query($params));

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $header[0] = "Authorization: WHM root:" . $this->apiKey;
        # Remove newlines from the hash
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        $data = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($data, TRUE);

        return $response;
    }

    protected function _userRequest($user, $module, $function, $params = array())
    {
        $params['cpanel_jsonapi_user'] = $user;
        $params['cpanel_jsonapi_module'] = $module;
        $params['cpanel_jsonapi_func'] = $function;

        $response = $this->_request('cpanel', $params);

        if (!empty($response['error'])) {
            throw new Exception($response['error']);
        }

        if (!empty($response['cpanelresult']['error'])) {
            throw new Exception($response['cpanelresult']['error']);
        }

        if (isset($response['cpanelresult']['postevent'])) {
            if ($response['cpanelresult']['postevent']['result'] != 1) {
                throw new Exception('API ERROR', $response['cpanelresult']['postevent']['result']);
            }
        } else {
            if (isset($response['cpanelresult']['event']['result']) != 1) {
                throw new Exception('API ERROR', $response['cpanelresult']['event']['result']);
            }
        }

        return $response;
    }

    public function getAccounts()
    {
        return array();
    }

}
