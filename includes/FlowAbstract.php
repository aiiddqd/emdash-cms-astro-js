<?php

namespace ProcessFlows;

abstract class FlowAbstract
{
    public static function init()
    {
        add_action(static::getActionNameWithSlug(), [static::class, 'handleAction']);
    }

    public static function scheduleAction($payload = [])
    {
        as_schedule_single_action(time(), static::getActionNameWithSlug(), [$payload], Plugin::$slug, true);
    }

    abstract public static function getSlug(): string;


    public static function getActionNameWithSlug(){
        return Plugin::$slug . '/' . static::getSlug();
    }
}