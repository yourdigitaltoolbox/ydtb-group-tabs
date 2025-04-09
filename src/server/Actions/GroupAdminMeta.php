<?php

namespace YDTBGroupTabs\Actions;

use YDTBGroupTabs\Interfaces\Provider;

class GroupAdminMeta implements Provider
{

    public function __construct()
    {
        // Constructor logic if needed
    }

    public function register()
    {
        add_action('bp_groups_admin_meta_boxes', [$this, 'bpgcp_add_admin_metabox']);
        add_action('bp_group_admin_edit_after', [$this, 'bpgcp_save_metabox_fields']);
    }

    public function bpgcp_add_admin_metabox()
    {
        add_meta_box(
            'bp_content_protection', // Meta box ID 
            'Content Protection', // Meta box title
            [$this, 'bpgcp_render_admin_metabox'], // Meta box callback function
            get_current_screen()->id, // Screen on which the metabox is displayed. In our case, the value is toplevel_page_bp-groups
            'side', // Where the meta box is displayed
            'core' // Meta box priority
        );
    }

    public function bpgcp_render_admin_metabox()
    {
        $group_id = intval($_GET['gid']);
        $hide_from_anonymous = intval(groups_get_groupmeta($group_id, 'hide_from_anonymous'));
        ?>

        <div class="bp-groups-settings-section" id="bp-groups-settings-section-content-protection">
            <fieldset>
                <legend>Hide the group's content from anonymous users?</legend>
                <label>
                    <input type="checkbox" name="hide_from_anonymous" value="1" <?php checked($hide_from_anonymous) ?>>
                    Yes
                </label>
            </fieldset>
        </div>

        <?php
    }


    public function bpgcp_save_metabox_fields($group_id)
    {
        $hide_from_anonymous = isset($_POST['hide_from_anonymous']) ? intval($_POST['hide_from_anonymous']) : 0;

        groups_update_groupmeta($group_id, 'hide_from_anonymous', $hide_from_anonymous);
    }

}