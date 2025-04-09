<?php

namespace YDTBGroupTabsRoot;

use YDTBGroupTabs\Utils\Updater;
use YDTBGroupTabs\Actions\GroupCreation;

class Plugin
{
    private $plugin_path;

    public function __construct()
    {
        if (!$this->plugin_checks()) {
            // still run the safe providers like the updater if the plugin checks fail
            foreach ($this->safeProviders() as $service) {
                (new $service)->register();
            }
            return;
        }
        $this->register();
    }

    /**
     * Register the providers
     */

    protected function providers()
    {
        return [
            GroupCreation::class,
            Updater::class
        ];
    }

    protected function safeProviders()
    {
        return [
            Updater::class
        ];
    }

    /**
     * Run each providers' register function
     */

    protected function register()
    {
        foreach ($this->providers() as $service) {
            (new $service)->register();
        }
    }

    /**
     * Check if the plugin has been built + anything else you want to check prior to booting the plugin
     */

    public function plugin_checks()
    {
        if (!function_exists('is_plugin_active'))
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');

        if (!is_plugin_active('buddyboss-platform-pro/buddyboss-platform-pro.php')) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>BuddyBoss Platform must be installed and activated for this plugin to work.</p></div>';
            });
            return false;
        }
        return true;
    }
}
