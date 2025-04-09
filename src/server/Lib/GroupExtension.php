<?php

namespace YDTBGroupTabs\Lib;

class GroupExtension extends \BP_Group_Extension
{
    public function __construct()
    {
        $group_id = bp_get_current_group_id();
        $group_tab_name = $group_id ? groups_get_groupmeta($group_id, 'group_tab_name') : '';

        $args = array(
            'slug' => 'group-test',
            'name' => !empty($group_tab_name) ? $group_tab_name : __('Custom Tab', 'ydtb-group-tabs'),
            'nav_item_position' => 200,
            'show_tab' => 'anyone',
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
        $group_extension_shortcode = groups_get_groupmeta($group_id, 'group_extension_shortcode');
        $group_tab_name = groups_get_groupmeta($group_id, 'group_tab_name');

        echo '<div class="bp-widget groups.group-admin">';
        if (!empty($group_extension_shortcode)) {
            echo do_shortcode($group_extension_shortcode);
        } else {
            echo '<p>' . __('No content has been set for this', 'ydtb-group-tabs') . '</p>';
        }
        echo '</div>';
    }

    public function settings_screen($group_id = null)
    {
        $shortcode = groups_get_groupmeta($group_id, 'group_extension_shortcode');
        $group_tab_name = groups_get_groupmeta($group_id, 'group_tab_name');
        $saved_sections = $this->get_saved_sections(); // Fetch saved Elementor sections
        ?>

        <h4><?php _e('Custom Tab Settings', 'ydtb-group-tabs'); ?></h4>
        <p><?php _e('Set a custom name for this tab.', 'ydtb-group-tabs'); ?></p>
        <input type="text" name="group_tab_name" id="group_tab_name" value="<?php echo esc_attr($group_tab_name); ?>"
            style="width: 100%;" placeholder="<?php _e('Enter tab name', 'ydtb-group-tabs'); ?>">
        <br><br>
        <p><?php _e('Select a saved section to display content on this tab.', 'ydtb-group-tabs'); ?></p>
        <select name="group_extension_shortcode" id="group_extension_shortcode" style="width: 100%;">
            <option value=""><?php _e('Select a section', 'ydtb-group-tabs'); ?></option>
            <?php foreach ($saved_sections as $section_id => $section_title): ?>
                <option value="[elementor-template id=&quot;<?php echo esc_attr($section_id); ?>&quot;]" <?php selected($shortcode, '[elementor-template id="' . $section_id . '"]'); ?>>
                    <?php echo esc_html($section_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>
        <?php
    }

    public function settings_screen_save($group_id = null)
    {
        $shortcode = isset($_POST['group_extension_shortcode']) ? sanitize_text_field($_POST['group_extension_shortcode']) : '';
        $group_tab_name = isset($_POST['group_tab_name']) ? sanitize_text_field($_POST['group_tab_name']) : '';

        groups_update_groupmeta($group_id, 'group_extension_shortcode', $shortcode);
        groups_update_groupmeta($group_id, 'group_tab_name', $group_tab_name);
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
