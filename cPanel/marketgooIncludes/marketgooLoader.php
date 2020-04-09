<?php

function marketgooLoader($class)
{

    $searchDirs = array(
        'Classes',
        'Classes' . DS . 'marketgooAPI',
        'Drivers',
        'Models'
    );
    if (preg_match("/^([A-Z]{1,3}[a-z]{0,10})([A-Z][a-zA-Z]+)Driver$/D", $class, $results)) {
        if ($results[1] . $results[2] == 'marketgoo') {
            $driverFile = __DIR__ . DS . 'Drivers' . DS . $class . '.php';

            if (!file_exists($driverFile)) {
                throw new Exception("Unable to find File for Driver: " . $class);
            }

            require_once $driverFile;
        } else {
            $driverFile = __DIR__ . DS . 'Drivers' . DS . $results[1] . DS . $class . '.php';

            if (!file_exists($driverFile)) {
                throw new Exception("Unable to find File for Driver: " . $class);
            }

            require_once $driverFile;
        }
    } else {
        $found = false;
        foreach ($searchDirs as $dir) {
            $classFile = __DIR__ . DS . $dir . DS . $class . '.php';
            if (file_exists($classFile)) {
                require_once $classFile;
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new Exception("Unable to find File for class: " . $class);
        }
    }
}

spl_autoload_register('marketgooLoader');

require_once __DIR__ . DS . 'marketgooMainController.php';
