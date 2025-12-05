<?php

namespace Flower;

class Services
{

    private static $instance = null;


    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        // Private constructor to prevent direct instantiation
    }

    private function __clone()
    {
        // Private clone method to prevent cloning
    }

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

}