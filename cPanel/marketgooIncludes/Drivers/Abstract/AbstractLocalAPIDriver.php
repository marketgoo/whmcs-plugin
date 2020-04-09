<?php

abstract class AbstractLocalAPIDriver
{

    function __construct($params)
    {
        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        $this->_connect($params);
    }

    public $connection;
    public $cpanel;

    /**
     *
     * @var type abstractConfig
     */
    public $config;

    abstract public function _connect($params);
}
