<?php 

namespace ProcessFlows;

//FlowInterface

interface FlowInterface {

    /**
     * Init flow and WP hooks
     * 
     * @return void
     */
    public static function init() : void;

    /**
     * Get flow slug for save and view to admin
     * 
     * @return string
     */
    public static function getSlug() : string;
}