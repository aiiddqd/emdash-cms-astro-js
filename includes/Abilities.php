<?php

namespace ProcessFlows;

add_action('abilities_api_categories_init', function () {
    wp_register_ability_category('flow_actions', array(
        'label' => 'Flow Actions',
        'description' => 'Abilities that are related to flow actions and automation.',
    ));
});

class Abilities
{
    public static function init()
    {

    }
}