<?php

namespace ProcessFlows;

$files = glob(get_stylesheet_directory().'/flows/*.php');
foreach ($files as $file) {
    require_once $file;
}