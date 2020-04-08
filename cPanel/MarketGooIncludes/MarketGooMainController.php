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

class MarketGooMainController extends AbstractControler
{

    function __construct($type, $dir)
    {
        try {

            $mainConfig = array(
                'dir' => $dir
                , 'type' => $type
            );

            parent::__construct($mainConfig);
            $localAPIClass = $this->type . 'LocalAPIDriver';

            if (!class_exists($localAPIClass)) {
                throw new Exception('Unable to find local API class:' . $localAPIClass);
            }

            MarketGooDriver::localAPI($localAPIClass, $this);

            $remoteAPIClass = 'MarketGooWHMCSAPI';

            if (!class_exists($remoteAPIClass)) {
                throw new SystemException('Unable to find remote API class:' . $remoteAPIClass);
            }

            MarketGooDriver::remoteAPI($remoteAPIClass, $this);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

}
