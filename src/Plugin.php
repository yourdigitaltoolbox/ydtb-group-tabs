<?php

namespace YDTBGroupTabsRoot;

use YDTBGroupTabs\Utils\Updater;

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
        // Register buddyBoss Specific extensions
        add_action('bp_include', [$this, 'registerBuddyBossExtensions']);
    }

    /**
     * Register the providers
     */

    protected function providers()
    {
        return [
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
     * Check before booting the plugin
     */

    public function plugin_checks()
    {
        if (!function_exists('is_plugin_active'))
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');

        // We are adding a tab to buddyboss groups, so we need to check if buddyboss is active
        if (!is_plugin_active('buddyboss-platform-pro/buddyboss-platform-pro.php')) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>BuddyBoss Platform must be installed and activated for the YDTB-Group-Tabs plugin to work.</p></div>';
            });
            return false;
        }

        return true;
    }


    public function registerBuddyBossExtensions()
    {
        if (bp_is_active('groups')) {
            // Register the group extension
            require(dirname(__FILE__) . '/server/Lib/GroupExtension.php');
            bp_register_group_extension('GroupExtension');
        }
    }
}
