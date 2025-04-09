<?php


namespace YDTBGroupTabs\Lib;
class GroupExtension extends \BP_Group_Extension
{
    public function __construct()
    {
        $args = array(
            'slug' => 'group-test',
            'name' => __('Test', 'buddypress'),
            'nav_item_position' => 200,
            'show_tab' => 'anyone',
            'screens' => array(
                'edit' => array(
                    'name' => __('Test', 'buddypress'),
                ),
                'create' => array('position' => 10, ),
            ),
        );
        parent::init($args);
    }

    public function display($group_id = null)
    {
        $group_id = bp_get_group_id();
        $group_extension_test = groups_get_groupmeta($group_id, 'group_extension_setting');
        echo 'Test: ' . esc_attr($group_extension_test);
        echo '<br>';
        echo 'You would write something here to display on the group page.';
        echo '<br>';
    }

    public function settings_screen($group_id = null)
    {
        $setting = groups_get_groupmeta($group_id, 'group_extension_setting');
        $group_types = bp_groups_get_group_type($group_id, false);
        ?>

        <h4><?php _e('Tab Settings', 'buddypress'); ?></h4>
        <?php

        echo 'Group Types: ' . $group_types;

        if (is_array($group_types)) {
            foreach ($group_types as $group_type) {
                $group_object = bp_groups_get_group_type_object($group_type);
                var_dump($group_object);
            }
        } elseif ($group_types) {
            $group_object = bp_groups_get_group_type_object($group_types);
            var_dump($group_object);
        }
        ?>
        <br>

        <div class="checkbox"></div>
        <input type="checkbox" name="group_extension_setting" id="group_extension_setting" value="1" <?php if ($setting == '1')
            echo ' checked="checked"'; ?> />&nbsp;
        <?php _e('Allow group members to do something', 'buddypress'); ?>
        </div>
        <br>
        <hr />
        <?php
    }

    public function settings_screen_save($group_id = null)
    {
        $setting = isset($_POST['group_extension_setting']) ? '1' : '0';
        groups_update_groupmeta($group_id, 'group_extension_setting', $setting);
    }

    public static function register()
    {
        if (bp_is_active('groups')) {
            bp_register_group_extension(__CLASS__);
        }
    }
}
