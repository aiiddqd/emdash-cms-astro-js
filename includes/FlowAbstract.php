<?php

namespace ProcessFlows;

abstract class FlowAbstract
{
    abstract public static function init();

    public function __invoke() {
        static::init();
        add_action(static::getActionNameWithSlug(), [static::class, 'handleAction']);
    }

    abstract public static function handleAction(array $payload);

    public static function scheduleAction($payload = [])
    {
        as_schedule_single_action(time(), static::getActionNameWithSlug(), [$payload], Plugin::$slug, true);
    }

    /**
     * Get the slug for the flow
     */
    abstract public static function getSlug(): string;


    public static function getActionNameWithSlug(){
        return Plugin::$slug . '/' . static::getSlug();
    }
}