<?php

namespace ProcessFlows;

abstract class FlowAbstract
{
    public static function scheduleAction($context = [])
    {
        as_schedule_single_action(time(), static::getActionNameWithSlug(), [$context], Plugin::$slug, true);
    }

    abstract public static function getSlug(): string;


    public static function getActionNameWithSlug(){
        return Plugin::$slug . '/' . static::getSlug();
    }
}