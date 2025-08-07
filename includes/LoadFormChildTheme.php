<?php

namespace ProcessFlows;

// load files from child theme
$files = glob(get_stylesheet_directory().'/flows/*.php');
foreach ($files as $file) {
    require_once $file;
}