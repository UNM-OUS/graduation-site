<?php

use DigraphCMS\UI\MenuBar\MenuBar;
use DigraphCMS\URL\URL;

$menu = (new MenuBar)
    ->setID('main-nav');
$menu->addURL(new URL('/'), 'Commencement');
$menu->addURL(new URL('/convocations/'), 'Departmental events');
$menu->addURL(new URL('/students/'), 'Student instructions');
$menu->addURL(new URL('/guests/'), 'Guest instructions');
$menu->addURL(new URL('/parking/'), 'Parking');
$menu->addURL(new URL('/photos/'), 'Photos');
echo $menu;
