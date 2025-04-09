<?php

namespace YDTBGroupTabs\Lib;

class GroupExtension extends \BP_Group_Extension
{
    public function __construct()
    {
        $group_id = bp_get_current_group_id();
        $group_tab_name = $group_id ? groups_get_groupmeta($group_id, 'group_tab_name') : '';
        $group_tab_visibility = $group_id ? groups_get_groupmeta($group_id, 'group_tab_visibility') : 'anyone';

        $args = array(
            'slug' => 'group-test',
            'name' => !empty($group_tab_name) ? $group_tab_name : __('Custom Tab', 'ydtb-group-tabs'),
            'nav_item_position' => 200,
            'show_tab' => $group_tab_visibility,
            'screens' => array(
                'edit' => array(
                    'name' => __('Tab Extension', 'ydtb-group-tabs'),
                ),
                'create' => array('position' => 10, ),
            ),
        );
        parent::init($args);
    }
    public function display($group_id = null)
    {
        $group_id = bp_get_group_id();
        $display_type = groups_get_groupmeta($group_id, 'group_display_type');
        $shortcode = groups_get_groupmeta($group_id, 'group_extension_shortcode');
        $elementor_shortcode = groups_get_groupmeta($group_id, 'group_extension_elementor');
        $redirect_url = groups_get_groupmeta($group_id, 'group_redirect_url');

        echo '<div class="bp-widget">';
        if ($display_type === 'shortcode' && !empty($shortcode)) {
            echo do_shortcode($shortcode);
        } elseif ($display_type === 'redirect' && !empty($redirect_url)) {
            echo 'Do A Redirect Here';
            echo $redirect_url;
            echo '<script>window.location.href = "' . esc_url($redirect_url) . '";</script>';
        } elseif ($display_type === 'elementor' && !empty($elementor_shortcode)) {
            echo do_shortcode($elementor_shortcode);
        } else {
            echo '<p>' . __('No content has been set for this', 'ydtb-group-tabs') . '</p>';
        }
        echo '</div>';
    }
    public function settings_screen($group_id = null)
    {
        $display_type = groups_get_groupmeta($group_id, 'group_display_type');
        $shortcode = groups_get_groupmeta($group_id, 'group_extension_shortcode');
        $elementor = groups_get_groupmeta($group_id, 'group_extension_elementor');
        $redirect_url = groups_get_groupmeta($group_id, 'group_redirect_url');
        $group_tab_name = groups_get_groupmeta($group_id, 'group_tab_name');
        $group_tab_visibility = groups_get_groupmeta($group_id, 'group_tab_visibility');
        $saved_sections = $this->get_saved_sections(); // Fetch saved Elementor sections
        ?>

        <h4><?php _e('Tab Extension Settings', 'ydtb-group-tabs'); ?></h4>
        <p><?php _e('Set a custom name for this custom group tab.', 'ydtb-group-tabs'); ?></p>
        <input type="text" name="group_tab_name" id="group_tab_name" value="<?php echo esc_attr($group_tab_name); ?>"
            style="width: 100%;" placeholder="<?php _e('Enter tab name', 'ydtb-group-tabs'); ?>">
        <br>
        <p><?php _e('Select what happens when the tab is clicked.', 'ydtb-group-tabs'); ?></p>
        <select name="group_display_type" id="group_display_type" style="width: 100%;">
            <option value="shortcode" <?php selected($display_type, 'shortcode'); ?>>
                <?php _e('Display Custom Shortcode', 'ydtb-group-tabs'); ?>
            </option>
            <option value="redirect" <?php selected($display_type, 'redirect'); ?>>
                <?php _e('Redirect User To URL', 'ydtb-group-tabs'); ?>
            </option>
            <option value="elementor" <?php selected($display_type, 'elementor'); ?>>
                <?php _e('Display Elementor Pro Saved Section', 'ydtb-group-tabs'); ?>
            </option>
        </select>
        <br><br>

        <div id="shortcode_field" style="display: <?php echo $display_type === 'shortcode' ? 'block' : 'none'; ?>;">
            <p><?php _e('Enter the custom shortcode to display.', 'ydtb-group-tabs'); ?></p>
            <input type="text" name="group_extension_shortcode" id="group_extension_shortcode"
                value="<?php echo esc_attr($shortcode); ?>" style="width: 100%;"
                placeholder="<?php _e('Enter shortcode', 'ydtb-group-tabs'); ?>">
        </div>

        <div id="redirect_field" style="display: <?php echo $display_type === 'redirect' ? 'block' : 'none'; ?>;">
            <p><?php _e('Enter the URL to redirect to.', 'ydtb-group-tabs'); ?></p>
            <input type="text" name="group_redirect_url" id="group_redirect_url" value="<?php echo esc_attr($redirect_url); ?>"
                style="width: 100%;" placeholder="<?php _e('Enter URL', 'ydtb-group-tabs'); ?>">
        </div>

        <div id="elementor_field" style="display: <?php echo $display_type === 'elementor' ? 'block' : 'none'; ?>;">
            <p><?php _e('Select an Elementor Pro saved section to display content on this tab.', 'ydtb-group-tabs'); ?></p>
            <select name="group_extension_elementor" id="group_extension_elementor" style="width: 100%;">
                <option value=""><?php _e('Select a section', 'ydtb-group-tabs'); ?></option>
                <?php foreach ($saved_sections as $section_id => $section_title): ?>
                    <option value="[elementor-template id=&quot;<?php echo esc_attr($section_id); ?>&quot;]" <?php selected($elementor, '[elementor-template id="' . $section_id . '"]'); ?>>
                        <?php echo esc_html($section_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const displayType = document.getElementById('group_display_type').value;
                document.getElementById('shortcode_field').style.display = displayType === 'shortcode' ? 'block' : 'none';
                document.getElementById('redirect_field').style.display = displayType === 'redirect' ? 'block' : 'none';
                document.getElementById('elementor_field').style.display = displayType === 'elementor' ? 'block' : 'none';
            });

            document.getElementById('group_display_type').addEventListener('change', function () {
                const displayType = this.value;
                document.getElementById('shortcode_field').style.display = displayType === 'shortcode' ? 'block' : 'none';
                document.getElementById('redirect_field').style.display = displayType === 'redirect' ? 'block' : 'none';
                document.getElementById('elementor_field').style.display = displayType === 'elementor' ? 'block' : 'none';
            });
        </script>

        <br>
        <p><?php _e('Set who can see this tab.', 'ydtb-group-tabs'); ?></p>
        <?php
        $visibility_options = array(
            'anyone' => __('Anyone ( Public )', 'ydtb-group-tabs'),
            'loggedin' => __('Logged In Users', 'ydtb-group-tabs'),
            'member' => __('Group Members', 'ydtb-group-tabs'),
            'mod' => __('Group Moderators', 'ydtb-group-tabs'),
            'admin' => __('Group Admins', 'ydtb-group-tabs'),
            'noone' => __('No One', 'ydtb-group-tabs'),
        );
        ?>
        <select name="group_tab_visibility" id="group_tab_visibility" style="width: 100%;">
            <?php foreach ($visibility_options as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($group_tab_visibility, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>
        <?php
    }

    public function settings_screen_save($group_id = null)
    {
        $display_type = isset($_POST['group_display_type']) ? sanitize_text_field($_POST['group_display_type']) : 'shortcode';
        $shortcode = isset($_POST['group_extension_shortcode']) ? sanitize_text_field($_POST['group_extension_shortcode']) : '';
        $elementor_shortcode = isset($_POST['group_extension_elementor']) ? sanitize_text_field($_POST['group_extension_elementor']) : '';
        $redirect_url = isset($_POST['group_redirect_url']) ? esc_url_raw($_POST['group_redirect_url']) : '';
        $group_tab_name = isset($_POST['group_tab_name']) ? sanitize_text_field($_POST['group_tab_name']) : '';
        $group_tab_visibility = isset($_POST['group_tab_visibility']) ? sanitize_text_field($_POST['group_tab_visibility']) : 'anyone';

        groups_update_groupmeta($group_id, 'group_display_type', $display_type);
        groups_update_groupmeta($group_id, 'group_extension_shortcode', $shortcode);
        groups_update_groupmeta($group_id, 'group_redirect_url', $redirect_url);
        groups_update_groupmeta($group_id, 'group_tab_name', $group_tab_name);
        groups_update_groupmeta($group_id, 'group_tab_visibility', $group_tab_visibility);
        groups_update_groupmeta($group_id, 'group_extension_elementor', $elementor_shortcode);
    }

    private function get_saved_sections()
    {
        $saved_sections = [];
        $args = array(
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_elementor_template_type',
                    'value' => 'section',
                ),
            ),
        );

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $saved_sections[get_the_ID()] = get_the_title();
            }
            wp_reset_postdata();
        }

        return $saved_sections;
    }

    public static function register()
    {
        if (bp_is_active('groups')) {
            bp_register_group_extension(__CLASS__);
        }
    }
}
