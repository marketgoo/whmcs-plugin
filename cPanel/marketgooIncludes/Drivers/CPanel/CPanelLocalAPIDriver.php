<?php

class CPanelLocalAPIDriver extends WHMLocalAPIDriver
{

    public $clientUsername;
    public $cpanel;

    function __construct($params)
    {
        $this->cpanel = $GLOBALS['CPANEL'];
        parent::__construct($params);
    }

    public function _connect($params)
    {
        $this->clientUsername = $this->getCurrentUser();
    }

    private function _callAPI1($module, $action, $params = array())
    {
        $ret = $this->cpanel->api1($module, $action, $params);

        if (!empty($ret['cpanelresult']['result']['errors'])) {
            throw new Exception(implode('/', $ret['cpanelresult']['result']['errors']));
        }

        return $ret['cpanelresult']['data']['result'];
    }

    function getCurrentUser()
    {
        return $this->cpanel->cpanelprint('$user');
    }

    function getFooter()
    {
        if ($this->cpanel->cpanelprint('$theme') == 'paper_lantern') {
            return $this->cpanel->footer();
        } else {
            return $this->_callAPI1('Branding', 'include', array('stdfooter.html'));
        }
    }

    function getHeader()
    {
        if ($this->cpanel->cpanelprint('$theme') == 'paper_lantern') {
            return $this->cpanel->header(MGLang::absoluteT('Marketing'));
        } else {
            return $this->_callAPI1('Branding', 'include', array('stdheader.html'));
        }
    }

    function getCurrentLang()
    {
        return $this->cpanel->cpanelprint('$lang');
    }

    function getUserMainDomain()
    {
        return $this->cpanel->cpanelprint('$CPDATA{\'DNS\'}');
    }

    function getTheme()
    {
        return $this->cpanel->cpanelprint('$theme');
    }

}
