<?php

namespace YDTBGroupTabs\Actions;

use YDTBGroupTabs\Interfaces\Provider;
use YDTBGroupTabs\Lib\GroupExtension;

class GroupCreation implements Provider
{
    public function __construct()
    {
        // Constructor logic if needed
    }

    public function register()
    {
        // Hook the registration method to bp_init
        add_action('bp_init', [GroupExtension::class, 'register']);
    }

}