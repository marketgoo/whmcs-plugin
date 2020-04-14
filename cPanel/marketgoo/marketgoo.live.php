<?php
require("/usr/local/cpanel/php/cpanel.php");
require_once './CpanelNVContainer.php';

class Mktgoo
{
    const CPANEL_USERS_DIR = "/var/cpanel/users/";

    public $cpanel;
    public $username     = "";
    public $props        = [];
    public $locale       = "en";
    public $translations = [];
    public $container;
    public $domains;
    public $config;

    function __construct()
    {
        $this->cpanel       = new CPANEL();
        $this->container    = new CpanelNVContainer($this->cpanel);
        $this->username     = $this->get_username();
        $this->props        = $this->get_user_props();
        $this->locale       = $this->get_locale();
        $this->config       = $this->load_config();
        $this->domains      = $this->get_domains();
        $this->translations = json_decode(file_get_contents("translations.json"), true);

        if ($this->check_if_new_code_arrived() !== false)
        {
            $this->domains = $this->get_domains();
        }

    }

    public function load_config()
    {
        $configFile = '/usr/local/cpanel/etc/marketgoo.ini';
        $config     = parse_ini_file($configFile);

        if (substr($config['endpoint'], 0, 4) != 'http')
        {
            $config['endpoint'] = 'http://'.$config['endpoint'];
        }

        return $config;
    }

    public function check_if_new_code_arrived()
    {
        if (!isset($_GET["signupok"]) || !isset($_GET['domain']) || !isset($_GET['pid']))
        {
            return false;
        }

        $uuid   = filter_var($_GET["signupok"], FILTER_SANITIZE_STRING);
        $domain = filter_var($_GET["domain"], FILTER_SANITIZE_STRING);
        $pid    = filter_var($_GET["pid"], FILTER_SANITIZE_STRING);

        if ($this->validate_uuid($uuid) || $uuid == 'terminate')
        {
            $this->container->offsetSet($domain, $uuid);
            $this->container->offsetSet($domain."_pid", $pid);

            return $uuid;
        }

        return false;
    }

    function __destruct()
    {
        $this->cpanel->end();
    }

    private function get_username()
    {
        return @$_ENV["REMOTE_USER"];
    }

    private function get_user_props()
    {
        $prop_file = self::CPANEL_USERS_DIR.$this->username;

        if (!file_exists($prop_file) || !is_readable($prop_file))
        {
            return;
        }

        $lines = array_filter(array_map(function($l) {
                $nl = trim($l);
                return strlen($nl) && $nl[0] == '#' ? null : $nl;
            }, explode("\n", file_get_contents($prop_file))));

        $props = [];

        foreach ($lines as $l)
        {
            $vars            = explode("=", $l);
            $props[$vars[0]] = @$vars[1];
        }

        return $props;
    }

    public function translate($seed)
    {
        if (!is_array($this->translations))
        {
            return $seed;
        }

        if (array_key_exists($seed, $this->translations[$this->locale]))
        {
            return $this->translations[$this->locale][$seed];
        }
        elseif (array_key_exists($seed, $this->translations[$this->locale]))
        {
            return $this->translations["en"][$seed];
        }
        else
        {
            return $seed;
        }
    }

    private function get_locale()
    {
        $cpanel_lang = $this->cpanel->fetch('$lang');
        $locale      = $cpanel_lang["cpanelresult"]["data"]["result"];

        return !is_null($locale) ? $locale : "en";
    }

    public function is_registered()
    {
        return !empty($this->uuid);
    }

    function set_data($key, $value)
    {
        $rc = $this->cpanel->api1("NVData", "set", [$key, $value]);
    }

    function get_data($key)
    {
        $this->cpanel->api2("NVData", "get", ["names" => $key]);

        $rc = $this->cpanel->get_result();

        if (count($rc))
        {
            return $rc[0]["value"];
        }

        return "";
    }

    public function get_domains()
    {
        $domains  = [];
        $response = $this->cpanel->uapi('DomainInfo', 'list_domains');
        $result   = $response['cpanelresult']['result'];

        if ($result['status'] == 1 || empty($result['errors']))
        {
            foreach ($result['data'] as $dd)
            {
                if (is_string($dd))
                {
                    $domains[] = $dd;
                    continue;
                }

                if (is_array($dd) && !empty($dd))
                {
                    foreach ($dd as $domain)
                    {
                        $domains[] = $domain;
                    }
                }
            }
        }

        return $this->hydrate_domains($domains);
    }

    public function hydrate_domains(array $domains)
    {
        $hydrated = [];
        //$active = $this->get_active_plans();

        foreach ($domains as $domain)
        {
            $uuid              = $this->container->offsetGet($domain);
            $pid               = $this->container->offsetGet($domain."_pid");
            $hydrated[$domain] = [
                'status'     => ($this->validate_uuid($uuid)) ? 1 : 0,
                'uuid'       => $uuid,
                'domainName' => $domain,
                'buyUrl'     => $this->obtain_buy_url($domain),
                'loginUrl'   => '',//$this->obtain_login_url($domain),
                'pid'        => $pid
            ];
        }

        return $hydrated;
    }

    public function obtain_buy_url($domain)
    {
        return sprintf('%s/modules/servers/marketgoo/cPanelCheck/cPanelCheck.php?username=%s&domain=%s', $this->config['endpoint'], $this->username, $domain);
    }

    public function get_buy_plans()
    {
        $response = $this->invokeWhmcs('GetProducts');
        if (!isset($response['result']) || $response['result'] != "success")
        {
            return [];
        }
        $plans = [];
        foreach ($response['products'] as $products)
        {
            foreach ($products as $product)
            {
                $plans[] = $product;
            }
        }
        return $plans;
    }

    public function get_active_plans()
    {
        $response = invokeWhmcs('GetActiveProducts', ['username' => $this->username]);
        if (!isset($response['result']) || $response['result'] != "success")
        {
            return $plans;
        }
        $plans = [];
        foreach ($response['products'] as $products)
        {
            foreach ($products as $product)
            {
                $plans[] = $product;
            }
        }
        return $plans;
    }

    private function invokeWhmcs($action, $additional = [])
    {
        if (file_exists('token.php'))
        {
            include 'token.php';
            
            if (!$token || $token == '')
            {
                return [];
            }
        }
        else
        {
            return [];
        }
        $params = array_merge($additional, [
            'action' => $action,
            'token'  => $token,
        ]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->config['endpoint'].'/modules/servers/marketgoo/tokenCheck/getProductsFromAPI.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $response = curl_exec($ch);
        
        if (curl_error($ch) && curl_error($ch) != "")
        {
            return [];
        }
        curl_close($ch);

        return json_decode($response, true);
    }

    public function validate_uuid($uuid)
    {
        return (!empty($uuid) && is_string($uuid) && strlen($uuid) == 40);
    }

    public function html_header()
    {
        $this->cpanel->api1("Branding", "include", ["stdheader.html"]);
        $html = $this->cpanel->get_result();

        return preg_replace(["/index\.html/", "@images/@"], ["../index.html", "../images/"], $html);
    }

    public function html_footer()
    {
        $this->cpanel->api1("Branding", "include", ["stdfooter.html"]);
        $html = $this->cpanel->get_result();
        return preg_replace(["/index\.html/", "@images/@"], ["../index.html", "../images/"], $html);
    }

    public function user_name()
    {
        return $this->username;
    }

    public function user_ip()
    {
        return isset($this->props["IP"]) ? $this->props["IP"] : @$_SERVER["SERVER_ADDR"];
    }

    public function user_language()
    {
        return strlen($this->locale) > 1 ? substr($this->locale, 0, 2) : "en";
    }

    public function user_country()
    {
        return strlen($this->locale) > 4 ? substr($this->locale, -2) : $this->user_language();
    }

    function target_for_item($item)
    {
        switch ($item)
        {
            case "seopack": return "";
            case "web": return "web";
            case "links": return "links";
            case "social": return "social";
            case "competitors": return "competitors";
            case "results": return "results";
        }

        return "";
    }

    private function marketgooRequest($method, $params = [], $additional = [], $endpoint, $token)
    {
        $ch         = curl_init();
        $url        = 'https://'.$endpoint.'/api'.$this->buildQuery($method, $params, $additional);
        $curlParams = [
            CURLOPT_URL        => $url,
            CURLOPT_HTTPHEADER => ["X-Auth-Token: ".$token,
                "Content-Type: application/x-www-form-urlencoded",
                "Accept: */*"],
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true
        ];

        if (strtolower($method) == 'post' || strtolower($method) == 'put')
        {
            $curlParams[CURLOPT_POST] = true;
            $curlParams[CURLOPT_POSTFIELDS] = http_build_query($additional);
        }

        curl_setopt_array($ch, $curlParams);
        $result = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $info = curl_getinfo($ch);

        if ($httpCode >= 400 && $httpCode < 500)
        {
            throw new \Exception('CODE: '.$httpCode.'. '.$result);
        }

        return $info['redirect_url'];
    }

    private function buildQuery($method, $params, $additional)
    {
        $string = '';

        foreach ($params as $key => $value)
        {
            $string .= '/'.$key.'/'.$value;
        }

        if (!empty($additional) && strtolower($method) == 'get')
        {
            $string .= '?'.http_build_query($additional);
        }

        return rtrim($string, '/');
    }
}
?>
