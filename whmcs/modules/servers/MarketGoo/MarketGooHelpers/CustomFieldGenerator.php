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

class CustomFieldGenerator extends AbstractGenerator
{

    public function __construct($id, $name)
    {
        parent::__construct($id, $name);
    }

    public function generate($options = array())
    {
        foreach ($options as $option) {
            $exists = $this->findOption($option);
            if (!isset($exists['id']) || !is_numeric($exists['id'])) {
                $this->safeQuery("INSERT INTO tblcustomfields(type, relid, fieldname, description, fieldtype, required, showorder, showinvoice, sortorder) VALUES('product', ?, ?, ?, 'text', ?, ?, ?, ?)", array(
                    $this->id,
                    $this->generateFieldname($option),
                    $option['description'],
                    $option['required'],
                    $option['showorder'],
                    $option['showinvoice'],
                    $option['sortorder'])
                );
            }
        }

        return true;
    }

    public function checkIfAlreadyGenerated($options = array())
    {
        foreach ($options as $option) {
            $exists = $this->findOption($option);
            if (!isset($exists['id']) || !is_numeric($exists['id']))
                return false;
        }

        return true;
    }

    public function findOption(array $option)
    {
        return $this->fetch_assoc($this->safeQuery("SELECT id FROM tblcustomfields WHERE relid=? AND type='product' AND fieldname LIKE ?",
                array($this->id, $option['fieldkey'].'%'))
        );
    }

    private function generateFieldname(array $option)
    {
        return sprintf('%s|%s', $option['fieldkey'], $option['fieldname']);
    }

}
