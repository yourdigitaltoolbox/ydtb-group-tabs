<?php

namespace YDTBGroupTabs\Actions;

use YDTBGroupTabs\Interfaces\Provider;

class GroupFormMeta implements Provider
{

    public function __construct()
    {
        // Constructor logic if needed
    }

    public function register()
    {
        add_action('bp_after_group_settings_creation_step', [$this, 'bpgcp_render_settings_in_create_form']);
        add_action('groups_create_group_step_save_group-settings', [$this, 'bpgcp_save_settings_from_create_form']);

        add_action('bp_after_group_settings_admin', [$this, 'bpgcp_render_settings_in_edit_form']);
        add_action('groups_group_settings_edited', [$this, 'bpgcp_save_settings_from_edit_form']);
    }

    public function bpgcp_render_settings_in_create_form()
    {
        ?>

        <fieldset class="radio content-privacy">
            <legend>Content Protection</legend>

            <p>Hide the group's content from anonymous users?</p>

            <label>
                <input type="checkbox" name="hide_from_anonymous" value="1">
                Yes
            </label>

        </fieldset>

        <?php
    }


    public function bpgcp_save_settings_from_create_form()
    {
        $group_id = bp_get_current_group_id();

        // bp_get_current_group_id() may return 0 at the first step
        if ($group_id === 0) {
            $group_id = buddypress()->groups->new_group_id;
        }

        $hide_from_anonymous = isset($_POST['hide_from_anonymous']) ? intval($_POST['hide_from_anonymous']) : 0;

        groups_update_groupmeta($group_id, 'hide_from_anonymous', $hide_from_anonymous);
    }

    public function bpgcp_render_settings_in_edit_form()
    {
        $group_id = bp_get_current_group_id();
        $hide_from_anonymous = intval(groups_get_groupmeta($group_id, 'hide_from_anonymous'));
        ?>

        <fieldset class="radio content-privacy">
            <legend>Content Protection</legend>

            <p>Hide the group's content from anonymous users?</p>

            <label>
                <input type="checkbox" name="hide_from_anonymous" value="1" <?php checked($hide_from_anonymous) ?>>
                Yes
            </label>

        </fieldset>

        <?php
    }

    public function bpgcp_save_settings_from_edit_form($group_id)
    {
        $hide_from_anonymous = isset($_POST['hide_from_anonymous']) ? intval($_POST['hide_from_anonymous']) : 0;

        groups_update_groupmeta($group_id, 'hide_from_anonymous', $hide_from_anonymous);
    }

}