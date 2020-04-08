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

use Illuminate\Database\Capsule\Manager as DB;

abstract class AbstractGenerator
{
    protected $id;
    protected $name;
    
    protected function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
    
    abstract protected function generate($options = array());
    
    protected function safeQuery($query, $params = false)
    {
        if ($params)
        {
            foreach ($params as &$v)
            {
                $v = $this->real_escape_string($v);
            }
            $sql_query = vsprintf(str_replace("?", "'%s'", $query), $params);
            $sql_query = $this->query($sql_query);
        }
        else
        {
            $sql_query = $this->query($query);
        }

        return ($sql_query);
    }
    
    protected function query($query, $params = array()) {
        $statement = DB::connection()
                ->getPdo()
                ->prepare($query);
        $statement->execute($params);
        return $statement;
    }
    protected function real_escape_string($string)
    {  
        return substr(DB::connection()->getPdo()->quote($string),1,-1);
    }
    protected function fetch_assoc($query)
    {
        return $query->fetch(\PDO::FETCH_ASSOC);
    }
    protected function fetch_array($query)
    {
        return $query->fetch(\PDO::FETCH_BOTH);
    }
    protected function insert_id()
    {
        return DB::connection()
                ->getPdo()
                ->lastInsertId();
    }

}
