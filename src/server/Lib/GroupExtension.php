<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class GroupExtension extends \BP_Group_Extension
{
    public function __construct()
    {
        $args = array(
            'slug' => 'ydtb-tabs',
            'name' => 'YDTB Tabs',
            'nav_item_position' => 200,
            'show_tab' => "noone",
            'screens' => array(
                'edit' => array(
                    'name' => __('Custom Tabs', 'ydtb-group-tabs'),
                ),
                'create' => array('position' => 10, ),
            ),
        );
        parent::init($args);
        // Add the redirection handler
        add_action('template_redirect', [$this, 'handle_tab_redirection']);
    }

    protected function setup_display_hooks()
    {
        if (!bp_is_group()) {
            return;
        }

        $bp = buddypress();
        $group_id = bp_get_current_group_id();

        // Retrieve the stored tabs data
        $tabs_data = groups_get_groupmeta($group_id, 'ydtb_tabs_data', true);
        $tabs_data = is_array($tabs_data) ? $tabs_data : [];

        if (bp_is_active('groups') && !empty($bp->groups->current_group)) {
            $group_link = $bp->root_domain . '/' . bp_get_groups_root_slug() . '/' . $bp->groups->current_group->slug . '/';
            $user_access = $bp->groups->current_group->user_has_access;

            global $bp;

            foreach ($tabs_data as $tab) {
                // Check visibility
                if (!$this->user_has_tab_access($tab['visibility'])) {
                    continue;
                }

                // Prepare the subnav item arguments
                $subnav_args = array(
                    'name' => isset($tab['name']) ? esc_html($tab['name']) : '',
                    'slug' => isset($tab['slug']) ? sanitize_title($tab['slug']) : '',
                    'parent_url' => $group_link,
                    'parent_slug' => $bp->groups->current_group->slug,
                    'screen_function' => [$this, 'bp_group_custom'],
                    'user_has_access' => $user_access,
                    'item_css_id' => isset($tab['slug']) ? sanitize_title($tab['slug']) : '',
                );

                // Ensure all required fields are set before adding the subnav item
                if (!empty($subnav_args['name']) && !empty($subnav_args['slug'])) {
                    bp_core_new_subnav_item($subnav_args);
                }
            }
        }
    }

    /**
     * Check if the current user has access to a tab based on its visibility setting.
     *
     * @param string $visibility The visibility setting of the tab.
     * @return bool True if the user has access, false otherwise.
     */
    private function user_has_tab_access($visibility)
    {
        switch ($visibility) {
            case 'anyone':
                return true;
            case 'loggedin':
                return is_user_logged_in();
            case 'member':
                return groups_is_user_member(get_current_user_id(), bp_get_current_group_id());
            case 'mod':
                return groups_is_user_mod(get_current_user_id(), bp_get_current_group_id());
            case 'admin':
                return groups_is_user_admin(get_current_user_id(), bp_get_current_group_id());
            case 'noone':
            default:
                return false;
        }
    }

    public function settings_screen($group_id = null)
    {
        $group_id = isset($group_id) ? $group_id : bp_get_current_group_id();
        $group_meta = groups_get_groupmeta($group_id, 'ydtb_tabs_data', true);
        $group_meta = is_array($group_meta) ? $group_meta : [];
        $saved_sections = $this->is_elementor_active() ? $this->get_saved_sections() : [];
        ?>

        <div id="ydtb-tabs-settings">
            <div class="tab">
                <?php foreach ($group_meta as $index => $tab): ?>
                    <button type="button" class="tablinks" onclick="openTab(event, 'tab-<?php echo $index; ?>')">
                        <?php echo esc_html($tab['name']); ?>
                    </button>
                <?php endforeach; ?>
                <button type="button" class="tablinks" id="new-tab-button" onclick="addNewTab()">+ Add Tab</button>
            </div>
            <div style="margin-top: 20px;"></div>
            <div id="tab-content-container" style="border-radius: 5px; border: 1px solid #ccc; padding: 25px;">
                <?php foreach ($group_meta as $index => $tab): ?>
                    <div id="tab-<?php echo $index; ?>" class="tabcontent">
                        <label>Name: <input type="text" name="ydtb_tabs[<?php echo $index; ?>][name]"
                                value="<?php echo esc_attr($tab['name']); ?>"></label>
                        <label>Slug:
                            <input type="text" name="ydtb_tabs[<?php echo $index; ?>][slug]"
                                value="<?php echo esc_attr($tab['slug'] ?? ''); ?>" <?php echo empty($tab['slug']) ? '' : 'data-user-edited="true"'; ?>>
                        </label>
                        <label>Type:
                            <select name="ydtb_tabs[<?php echo $index; ?>][type]" class="tab-type-selector"
                                data-index="<?php echo $index; ?>">
                                <option value="shortcode" <?php selected($tab['type'], 'shortcode'); ?>>Shortcode</option>
                                <option value="url_redirect" <?php selected($tab['type'], 'url_redirect'); ?>>URL Redirect</option>
                                <?php if ($this->is_elementor_active()): ?>
                                    <option value="saved_section" <?php selected($tab['type'], 'saved_section'); ?>>Saved Section
                                    </option>
                                <?php endif; ?>
                            </select>
                        </label>
                        <div class="tab-type-fields" id="fields-<?php echo $index; ?>">
                            <?php if ($tab['type'] === 'saved_section' && $this->is_elementor_active()): ?>
                                <label>Saved Section:
                                    <select name="ydtb_tabs[<?php echo $index; ?>][content]">
                                        <option value=""><?php _e('Select a section', 'ydtb-group-tabs'); ?></option>
                                        <?php foreach ($saved_sections as $section_id => $section_title): ?>
                                            <option value="<?php echo esc_attr($section_id); ?>" <?php selected($tab['content'], esc_attr($section_id)); ?>>
                                                <?php echo esc_html($section_title); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            <?php elseif ($tab['type'] === 'url_redirect'): ?>
                                <label>Redirect URL: <input type="text" name="ydtb_tabs[<?php echo $index; ?>][content]"
                                        value="<?php echo esc_attr($tab['content']); ?>"></label>
                            <?php elseif ($tab['type'] === 'shortcode'): ?>
                                <label>Shortcode:
                                    <input type="text" name="ydtb_tabs[<?php echo $index; ?>][content]"
                                        value="<?php echo esc_attr($tab['content']); ?>">
                                </label>
                            <?php endif; ?>
                        </div>
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
                        $current_visibility = isset($tab['visibility']) ? $tab['visibility'] : 'anyone';
                        ?>
                        <select name="ydtb_tabs[<?php echo $index; ?>][visibility]" style="width: 100%;">
                            <?php foreach ($visibility_options as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($current_visibility, $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <br><br>
                        <div style="text-align: right;">
                            <button type="button" style="background-color: #f44336;" onclick="removeTab(this)">Remove Tab</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-bottom: 20px;"></div>
        </div>

        <script>
            function generateSlug(name) {
                // Convert spaces to dashes and remove invalid characters
                return name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-_]/g, '');
            }

            function validateSlug(slug, existingSlugs) {
                if (!slug.trim()) {
                    return 'Slug cannot be empty.';
                }
                if (!/^[a-z0-9-_]+$/.test(slug)) {
                    return 'Slug can only contain letters, numbers, underscores, and dashes.';
                }
                if (existingSlugs.includes(slug)) {
                    return 'Slug must be unique.';
                }
                return '';
            }

            function openTab(evt, tabId) {
                var i, tabcontent, tablinks;
                tabcontent = document.getElementsByClassName("tabcontent");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }
                tablinks = document.getElementsByClassName("tablinks");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].className = tablinks[i].className.replace(" active", "");
                }
                document.getElementById(tabId).style.display = "block";
                evt.currentTarget.className += " active";
            }

            function addNewTab() {
                var tabContainer = document.getElementById("ydtb-tabs-settings");
                var tabLinks = tabContainer.querySelector(".tab");
                var tabContentsBox = tabContainer.querySelector("#tab-content-container"); // Select the box where tabs go
                var tabContents = tabContentsBox.querySelectorAll(".tabcontent");
                var newIndex = tabContents.length;

                // Generate a slug for the new tab
                var defaultName = "New Tab";
                var defaultSlug = generateSlug(defaultName);

                // Create new tab button
                var newTabButton = document.createElement("button");
                newTabButton.className = "tablinks";
                newTabButton.textContent = defaultName;
                newTabButton.setAttribute("type", "button");
                newTabButton.setAttribute("onclick", "openTab(event, 'tab-" + newIndex + "')");
                newTabButton.style.marginRight = "5px";
                tabLinks.insertBefore(newTabButton, tabLinks.lastElementChild);

                // Check if Elementor Pro is active
                var isElementorProActive = <?php echo json_encode($this->is_elementor_active()); ?>;

                // Create new tab content
                var newTabContent = document.createElement("div");
                newTabContent.id = "tab-" + newIndex;
                newTabContent.className = "tabcontent";
                newTabContent.innerHTML = `
        <label>Name: <input type="text" name="ydtb_tabs[${newIndex}][name]" value="${defaultName}"></label>
        <label>Slug: <input type="text" name="ydtb_tabs[${newIndex}][slug]" value="${defaultSlug}"></label>
        <label>Type:
            <select name="ydtb_tabs[${newIndex}][type]" class="tab-type-selector" data-index="${newIndex}">
                <option value="url_redirect">URL Redirect</option>
                <option value="shortcode">Shortcode</option>
                ${isElementorProActive ? '<option value="saved_section">Saved Section</option>' : ''}
            </select>
        </label>
        <div class="tab-type-fields" id="fields-${newIndex}">
            <!-- Fields will be dynamically added here -->
        </div>
        <p><?php _e('Set who can see this tab.', 'ydtb-group-tabs'); ?></p>
        <select name="ydtb_tabs[${newIndex}][visibility]" style="width: 100%;">
            <option value="anyone"><?php _e('Anyone ( Public )', 'ydtb-group-tabs'); ?></option>
            <option value="loggedin"><?php _e('Logged In Users', 'ydtb-group-tabs'); ?></option>
            <option value="member"><?php _e('Group Members', 'ydtb-group-tabs'); ?></option>
            <option value="mod"><?php _e('Group Moderators', 'ydtb-group-tabs'); ?></option>
            <option value="admin"><?php _e('Group Admins', 'ydtb-group-tabs'); ?></option>
            <option value="noone"><?php _e('No One', 'ydtb-group-tabs'); ?></option>
        </select>
        <br><br>
        <div style="text-align: right;">
            <button type="button" style="background-color: #f44336;" onclick="removeTab(this)">Remove Tab</button>
        </div>
    `;
                tabContentsBox.appendChild(newTabContent); // Append the new tab content to the box

                // Open the new tab
                openTab({ currentTarget: newTabButton }, "tab-" + newIndex);

                // Trigger the change event for all tab-type-selectors on initial load
                document.querySelectorAll('.tab-type-selector').forEach(function (selector) {
                    var event = new Event('change', { bubbles: true });
                    selector.dispatchEvent(event);
                });
            }

            function removeTab(button) {
                var tabContent = button.closest('.tabcontent');
                var tabId = tabContent.id;
                var tabButton = document.querySelector(`.tablinks[onclick*="${tabId}"]`);

                // Remove the tab content and button
                tabContent.remove();
                if (tabButton) tabButton.remove();

                // After removing, show the first available tab
                var remainingTabs = document.querySelectorAll('.tabcontent');
                if (remainingTabs.length > 0) {
                    var firstTab = remainingTabs[0];
                    var firstTabId = firstTab.id;
                    var firstTabButton = document.querySelector(`.tablinks[onclick*="${firstTabId}"]`);
                    if (firstTabButton) {
                        openTab({ currentTarget: firstTabButton }, firstTabId);
                    }
                }
            }

            document.addEventListener('input', function (e) {
                if (e.target.name && e.target.name.includes('[name]')) {
                    const nameInput = e.target;
                    const index = nameInput.name.match(/\[(\d+)\]/)[1];
                    const slugInput = document.querySelector(`input[name="ydtb_tabs[${index}][slug]"]`);
                    const errorContainer = slugInput.nextElementSibling;

                    // Only generate slug if the slug field does not have the data-user-edited attribute
                    if (!slugInput.hasAttribute('data-user-edited')) {
                        slugInput.value = generateSlug(nameInput.value);
                    }

                    // Validate slug
                    const existingSlugs = Array.from(document.querySelectorAll('input[name*="[slug]"]'))
                        .map(input => input.value)
                        .filter(value => value !== slugInput.value);

                    const errorMessage = validateSlug(slugInput.value, existingSlugs);

                    if (errorMessage) {
                        slugInput.style.borderColor = 'red';
                        if (!errorContainer || !errorContainer.classList.contains('slug-error')) {
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'slug-error';
                            errorDiv.style.color = 'red';
                            errorDiv.textContent = errorMessage;
                            slugInput.parentNode.appendChild(errorDiv);
                        } else {
                            errorContainer.textContent = errorMessage;
                        }
                    } else {
                        slugInput.style.borderColor = '';
                        if (errorContainer && errorContainer.classList.contains('slug-error')) {
                            errorContainer.remove();
                        }
                    }
                }
            });

            document.addEventListener('focus', function (e) {
                if (e.target.name && e.target.name.includes('[slug]')) {
                    const slugInput = e.target;
                    // Mark the slug field as manually edited when the user focuses on it
                    slugInput.setAttribute('data-user-edited', 'true');
                }
            }, true);

            document.addEventListener('blur', function (e) {
                if (e.target.name && e.target.name.includes('[slug]')) {
                    const slugInput = e.target;
                    const errorContainer = slugInput.nextElementSibling;

                    // Validate slug on blur
                    const existingSlugs = Array.from(document.querySelectorAll('input[name*="[slug]"]'))
                        .map(input => input.value)
                        .filter(value => value !== slugInput.value);

                    const errorMessage = validateSlug(slugInput.value, existingSlugs);

                    if (errorMessage) {
                        slugInput.style.borderColor = 'red';
                        if (!errorContainer || !errorContainer.classList.contains('slug-error')) {
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'slug-error';
                            errorDiv.style.color = 'red';
                            errorDiv.textContent = errorMessage;
                            slugInput.parentNode.appendChild(errorDiv);
                        } else {
                            errorContainer.textContent = errorMessage;
                        }
                    } else {
                        slugInput.style.borderColor = '';
                        if (errorContainer && errorContainer.classList.contains('slug-error')) {
                            errorContainer.remove();
                        }
                    }
                }
            }, true);

            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('tab-type-selector')) {
                    var index = e.target.getAttribute('data-index');
                    var fieldsContainer = document.getElementById('fields-' + index);
                    fieldsContainer.innerHTML = '';

                    if (e.target.value === 'saved_section') {
                        fieldsContainer.innerHTML = `
                            <label>Saved Section:
                                <select name="ydtb_tabs[${index}][content]">
                                    <option value=""><?php _e('Select a section', 'ydtb-group-tabs'); ?></option>
                                    <?php foreach ($saved_sections as $section_id => $section_title): ?>
                                        <option value="<?php echo esc_attr($section_id); ?>">
                                            <?php echo esc_html($section_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        `;
                    } else if (e.target.value === 'url_redirect') {
                        fieldsContainer.innerHTML = `
                            <label>Redirect URL: <input type="text" name="ydtb_tabs[${index}][content]"></label>
                        `;
                    } else if (e.target.value === 'shortcode') {
                        fieldsContainer.innerHTML = `
                            <label>Shortcode: <input type="text" name="ydtb_tabs[${index}][content]"></label>
                        `;
                    }
                }
            });

            // Open the first tab by default
            openTab(event, 'tab-0');
        </script>
        <?php
    }

    public function settings_screen_save($group_id = null)
    {
        $group_id = isset($group_id) ? $group_id : bp_get_current_group_id();
        $validation_errors = array();

        if (isset($_POST['ydtb_tabs']) && is_array($_POST['ydtb_tabs'])) {
            $tabs_data = array();

            foreach ($_POST['ydtb_tabs'] as $index => $tab) {
                $tab_name = sanitize_text_field($tab['name'] ?? '');
                $tab_type = sanitize_text_field($tab['type'] ?? '');
                $tab_content = $tab['content'] ?? '';
                $tab_visibility = sanitize_text_field($tab['visibility'] ?? 'anyone');

                // Validate tab name
                if (empty($tab_name)) {
                    $validation_errors[] = sprintf(__('Tab #%d: Name is required.', 'ydtb-group-tabs'), $index + 1);
                }

                // Validate tab type
                if (empty($tab_type)) {
                    $validation_errors[] = sprintf(__('Tab #%d: Type is required.', 'ydtb-group-tabs'), $index + 1);
                } elseif (!in_array($tab_type, ['saved_section', 'url_redirect', 'shortcode'], true)) {
                    $validation_errors[] = sprintf(__('Tab #%d: Invalid type selected.', 'ydtb-group-tabs'), $index + 1);
                }

                // Validate tab content based on type
                if ($tab_type === 'url_redirect' && !empty($tab_content) && !filter_var($tab_content, FILTER_VALIDATE_URL)) {
                    $validation_errors[] = sprintf(__('Tab #%d: Invalid URL provided for redirect.', 'ydtb-group-tabs'), $index + 1);
                }

                // If no validation errors for this tab, add it to the data array
                if (empty($validation_errors)) {
                    $tabs_data[] = array(
                        'name' => $tab_name,
                        'slug' => sanitize_title($tab['slug'] ?? ''), // Sanitize the slug
                        'type' => $tab_type,
                        'content' => wp_kses_post($tab_content),
                        'visibility' => $tab_visibility,
                    );
                }
            }

            // If there are validation errors, display them and return
            if (!empty($validation_errors)) {
                foreach ($validation_errors as $error) {
                    bp_core_add_message($error, 'error');
                }
                return;
            }

            // Save the validated data
            groups_update_groupmeta($group_id, 'ydtb_tabs_data', $tabs_data);
            bp_core_add_message(__('Tabs settings saved successfully.', 'ydtb-group-tabs'), 'success');
        } else {
            bp_core_add_message(__('No tabs data provided.', 'ydtb-group-tabs'), 'error');
        }
    }

    private function is_elementor_active()
    {
        return defined('ELEMENTOR_VERSION');
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

    public function bp_group_custom()
    {
        // add_action('bp_template_title', [$this, 'my_new_group_show_screen_title']);
        add_action('bp_template_content', [$this, 'new_group_tab_show_screen_content']);

        $templates = array('groups/single/plugins.php', 'plugin-template.php');
        if (strstr(locate_template($templates), 'groups/single/plugins.php')) {
            bp_core_load_template(apply_filters('bp_core_template_plugin', 'groups/single/plugins'));
        } else {
            bp_core_load_template(apply_filters('bp_core_template_plugin', 'plugin-template'));
        }

    }


    public function new_group_tab_show_screen_content()
    {
        $current_slug = bp_current_action(); // Get the current tab slug
        $group_id = bp_get_current_group_id(); // Get the current group ID

        // Retrieve the stored tabs data
        $tabs_data = groups_get_groupmeta($group_id, 'ydtb_tabs_data', true);
        $tabs_data = is_array($tabs_data) ? $tabs_data : [];

        // Find the current tab based on the slug
        $current_tab = null;
        foreach ($tabs_data as $tab) {
            if (sanitize_title($tab['slug']) === $current_slug) {
                $current_tab = $tab;
                break;
            }
        }

        // If no matching tab is found, display an error message
        if (!$current_tab) {
            echo '<p>' . __('Tab not found.', 'ydtb-group-tabs') . '</p>';
            return;
        }

        // Render the tab content based on its type
        switch ($current_tab['type']) {
            case 'shortcode':
                // Render the specified shortcode
                if (!empty($current_tab['content'])) {
                    echo do_shortcode($current_tab['content']);
                } else {
                    echo '<p>' . __('No shortcode provided.', 'ydtb-group-tabs') . '</p>';
                }
                break;

            case 'saved_section':
                // Render the Elementor section using the post ID
                if (!empty($current_tab['content']) && $this->is_elementor_active()) {
                    $section_id = intval($current_tab['content']);
                    echo Elementor\Plugin::instance()->frontend->get_builder_content_for_display($section_id);
                } else {
                    echo '<p>' . __('No section ID provided.', 'ydtb-group-tabs') . '</p>';
                }
                break;

            default:
                echo '<p>' . __('Invalid tab type.', 'ydtb-group-tabs') . '</p>';
                break;
        }
    }

    public function handle_tab_redirection()
    {
        if (!bp_is_group()) {
            return;
        }

        $current_slug = bp_current_action(); // Get the current tab slug
        $group_id = bp_get_current_group_id(); // Get the current group ID

        // Retrieve the stored tabs data
        $tabs_data = groups_get_groupmeta($group_id, 'ydtb_tabs_data', true);
        $tabs_data = is_array($tabs_data) ? $tabs_data : [];

        // Find the current tab based on the slug
        foreach ($tabs_data as $tab) {
            if (sanitize_title($tab['slug']) === $current_slug && $tab['type'] === 'url_redirect') {
                // Redirect to the specified URL
                if (!empty($tab['content']) && filter_var($tab['content'], FILTER_VALIDATE_URL)) {
                    wp_redirect(esc_url($tab['content']));
                    exit;
                } else {
                    wp_die(__('Invalid redirect URL.', 'ydtb-group-tabs'));
                }
            }
        }
    }
}
