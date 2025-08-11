<?php

namespace ProcessFlows;

abstract class FlowAbstract
{
    public static function scheduleAction($hook, $context = [])
    {
        as_schedule_single_action(time(), Plugin::$slug . '/' . $hook, [$context], Plugin::$slug, true);
    }
}