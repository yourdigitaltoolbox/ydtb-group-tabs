<?php

namespace YDTBGroupTabs\Provider;

use YDTBGroupTabs\Interfaces\Provider;
use YDTBGroupTabs\Commands\AdminNoticeCLI;

class CommandServiceProvider implements Provider
{
    public function register()
    {
        if (!defined('WP_CLI') || !WP_CLI) {
            return;
        }

        // \WP_CLI::add_command('hide-notice', AdminNoticeCLI::class);
    }
}