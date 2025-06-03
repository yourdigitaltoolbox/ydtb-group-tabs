<?php

namespace YDTBGroupTabsRoot;

use YDTBGroupTabs\Utils\Updater;

class Plugin
{
    private $plugin_path;

    public function __construct()
    {
        if (!$this->plugin_checks()) {
            foreach ($this->safeProviders() as $service) {
                (new $service)->register();
            }
            return;
        }
        $this->register();
        add_action('bp_include', [$this, 'registerBuddyBossExtensions']);

        // Add this filter for per-group default tab
        add_filter('bp_groups_default_extension', [$this, 'set_group_default_tab'], 10, 2);
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
            require(dirname(__FILE__) . '/server/Lib/GroupAccess.php');
            bp_register_group_extension('GroupExtension');
            bp_register_group_extension('GroupAccess');
        }
    }

    /**
     * Set the default tab for each group based on group meta.
     *
     * @param string $default_extension The default extension slug.
     * @param int $group_id The group ID.
     * @return string
     */
    public function set_group_default_tab($default_extension, $group_id = null)
    {
        if (!$group_id) {
            $group_id = bp_get_current_group_id();
        }
        if (!$group_id) {
            return $default_extension;
        }
        $custom_default = groups_get_groupmeta($group_id, 'ydtb_default_tab', true);
        if ($custom_default && $custom_default !== 'default') {
            return $custom_default;
        }
        return $default_extension;
    }
}
