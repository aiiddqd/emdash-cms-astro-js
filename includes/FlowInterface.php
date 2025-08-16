<?php 

namespace ProcessFlows;

//FlowInterface

interface FlowInterface {

    //add propertie (not function and not const) for flow - slug, title, description
    



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
    
    /**
     * Get flow title for save and view to admin
     * 
     * @return string
     */
    public static function getTitle() : string;
    
    /**
     * Get flow description for save and view to admin
     * 
     * @return string
     */
    public static function getDescription() : string;
    
    /**
     * Start actions for flow
     */
    public static function prepareActions() : mixed;
}