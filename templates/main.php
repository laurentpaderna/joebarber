<?php

require __DIR__.'/../vendor/autoload.php';

// Render content to template
$template = $GLOBALS['twig']->load('/views/main.html.twig');
echo $template->render([]);
