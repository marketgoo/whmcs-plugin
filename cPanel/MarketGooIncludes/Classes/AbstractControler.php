<?php

abstract class AbstractControler
{

    protected $templatesDir;
    protected $type;
    protected $dir;
    protected $page;
    public $configFile = '/usr/local/cpanel/etc/MarketGoo.ini';
    public $baseUrlConfig = array(
        'html' => 'index.live.php'
    );

    public function __construct($parent, $options = array())
    {
        foreach ($parent as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        if (!empty($this->dir) && empty($this->templatesDir)) {
            $this->templatesDir = $this->dir . DS . 'templates';
        }
    }

    protected function getHTML($file, $data = array())
    {
        $templateFile = $this->templatesDir . DS . $file . '.php';

        if (!file_exists($templateFile)) {
            throw new Exception("Unable to find html File:" . $templateFile);
        }

        try {
            ob_start();
            extract((array) $data);
            include($templateFile);
            $html = ob_get_clean();
        } catch (Exception $ex) {
            ob_clean();
            throw $ex;
        }

        return $html;
    }

    protected function getJSON($data = array())
    {
        return '<!--JSONRESPONSE#' . json_encode($data) . '#ENDJSONRESPONSE -->';
    }

    protected function getUrl($page, $action = null)
    {
        return $this->baseUrlConfig['html'] . '?page=' . $page . (($action) ? '&action=' . $action : '');
    }

    protected function getCurrentAction($action = null)
    {
        return $this->getUrl($this->page, $action);
    }

}
