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

        global $bp;
        $current_group_slug = $bp->groups->current_group->slug;
        $group_nav = $bp->groups->nav->get();

        // Build a lookup for custom tabs by slug for easy matching
        $custom_tabs_by_slug = [];
        foreach ($group_meta as $idx => $tab) {
            if (!empty($tab['slug'])) {
                $custom_tabs_by_slug[sanitize_title($tab['slug'])] = ['tab' => $tab, 'index' => $idx];
            }
        }

        // Prepare a combined list of nav items with info about whether they're custom
        $all_tabs = [];
        if (is_array($group_nav)) {
            foreach ($group_nav as $key => $nav_item) {
                if (preg_match('/^' . preg_quote($current_group_slug, '/') . '\/[^\/]+$/', $key)) {
                    $slug = basename($key);
                    $is_custom = isset($custom_tabs_by_slug[$slug]);
                    $all_tabs[] = [
                        'is_custom' => $is_custom,
                        'slug' => $slug,
                        'nav_item' => $nav_item,
                        'custom_tab' => $is_custom ? $custom_tabs_by_slug[$slug]['tab'] : null,
                        'custom_index' => $is_custom ? $custom_tabs_by_slug[$slug]['index'] : null,
                        'position' => isset($nav_item->position) ? intval($nav_item->position) : 9999,
                    ];
                }
            }
        }

        // Sort by position
        usort($all_tabs, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        ?>
        <div id="ydtb-tabs-accordion-settings">
            <div id="accordion-container">
                <?php foreach ($all_tabs as $tab_info): ?>
                    <?php if ($tab_info['is_custom']): ?>
                        <?php
                        $tab = $tab_info['custom_tab'];
                        $index = $tab_info['custom_index'];
                        ?>
                        <div class="accordion-item">
                            <div class="accordion-header-row" tabindex="0" aria-expanded="false">
                                <div style="display:flex; width:100%; align-items:center;">
                                    <span class="accordion-title" style="flex:1 1 auto; text-align:left;">
                                        <?php echo esc_html($tab['name']); ?>
                                    </span>
                                    <span style="margin-left:8px; color:#888;">
                                        - <?php echo esc_html($tab['slug']); ?>
                                    </span>
                                    <span style="flex:0 0 auto; text-align:right; font-weight:bold; margin-left:auto;">
                                        <?php echo esc_html($tab_info['position']); ?>
                                    </span>
                                    <span class="move-tab-buttons">
                                        <button type="button" class="move-tab-up"
                                            title="<?php esc_attr_e('Move Up', 'ydtb-group-tabs'); ?>">&#8593;</button>
                                        <button type="button" class="move-tab-down"
                                            title="<?php esc_attr_e('Move Down', 'ydtb-group-tabs'); ?>">&#8595;</button>
                                    </span>
                                    <button type="button" class="remove-accordion-tab"
                                        title="<?php esc_attr_e('Remove Tab', 'ydtb-group-tabs'); ?>">
                                        <!-- SVG as before -->
                                        <svg width="18" height="18" viewBox="0 0 20 20" fill="white" aria-hidden="true"
                                            focusable="false">
                                            <rect x="3" y="5.5" width="14" height="1.5" rx="0.75" fill="white" />
                                            <path
                                                d="M6.5 7.5V15.5M10 7.5V15.5M13.5 7.5V15.5M8.5 3.5H11.5C12.0523 3.5 12.5 3.94772 12.5 4.5V5.5H7.5V4.5C7.5 3.94772 7.94772 3.5 8.5 3.5Z"
                                                stroke="white" stroke-width="1.5" stroke-linecap="round" />
                                            <rect x="6.5" y="7.5" width="7" height="8" rx="1" fill="white" stroke="white"
                                                stroke-width="1" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="accordion-panel" style="display: none;">
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
                                        <option value="url_redirect" <?php selected($tab['type'], 'url_redirect'); ?>>URL Redirect
                                        </option>
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
                                <input type="hidden" class="tab-position-input" name="ydtb_tabs[<?php echo $index; ?>][position]"
                                    value="<?php echo esc_attr($tab_info['position']); ?>">
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="accordion-item">
                            <div class="accordion-header-row" tabindex="0" aria-expanded="false" style="cursor:default;">
                                <div style="display:flex; width:100%; align-items:center;">
                                    <span style="flex:1 1 auto; text-align:left;">
                                        <?php echo esc_html($tab_info['nav_item']->name); ?> -
                                        <?php echo esc_html($tab_info['slug']); ?>
                                    </span>
                                    <span style="flex:0 0 auto; text-align:right; font-weight:bold;">
                                        <?php echo esc_html($tab_info['position']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 20px;">
                <button type="button" id="add-accordion-tab">+ Add Tab</button>
            </div>
        </div>

        <style>
            .accordion-item {
                border: 1px solid #ccc;
                border-radius: 6px;
                margin-bottom: 14px;
                background: #fafbfc;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                padding: 0;
            }

            .accordion-header-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                border-radius: 6px 6px 0 0;
                background: #f1f1f1;
                border-bottom: 1px solid #ddd;
                transition: background 0.2s;
                cursor: pointer;
                padding: 12px 16px;
                outline: none;
                gap: 8px;
            }

            .accordion-header-row[aria-expanded="true"] {
                background: #e2e2e2;
            }

            .accordion-title {
                flex: 1 1 auto;
                text-align: left;
                font-size: 16px;
                font-weight: 500;
                color: #222;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .chevron {
                flex: 0 0 auto;
                display: inline-block;
                transition: transform 0.2s;
                margin-left: 16px;
                font-size: 18px;
                color: #888;
            }

            .accordion-header-row[aria-expanded="true"] .chevron {
                transform: rotate(90deg);
            }

            .remove-accordion-tab {
                background: #e53935 !important;
                /* Red background */
                border: none;
                padding: 4px;
                margin-left: 8px;
                cursor: pointer;
                display: flex;
                align-items: center;
                border-radius: 3px;
                transition: background 0.15s;
                box-shadow: 0 1px 2px rgba(229, 57, 53, 0.10);
            }

            .remove-accordion-tab:hover,
            .remove-accordion-tab:focus {
                background: #b71c1c !important;
            }

            .remove-accordion-tab svg {
                display: block;
                pointer-events: none;
            }

            .accordion-panel {
                padding: 16px;
                border-bottom: none;
                background: #fff;
                border-radius: 0 0 6px 6px;
            }

            .move-tab-buttons {
                display: flex;
                gap: 2px;
                margin-right: 8px;
            }

            .move-tab-up,
            .move-tab-down {
                background: #e0e0e0;
                border: none;
                border-radius: 3px;
                padding: 2px 6px;
                cursor: pointer;
                font-size: 14px;
                line-height: 1;
                transition: background 0.15s;
            }

            .move-tab-up:hover,
            .move-tab-down:hover {
                background: #bdbdbd;
            }
        </style>

        <script>
            // Accordion logic
            document.addEventListener('DOMContentLoaded', function () {
                const headerRows = document.querySelectorAll('.accordion-header-row');
                headerRows.forEach((row, idx) => {
                    row.addEventListener('click', function (e) {
                        // Prevent toggle if clicking the remove button
                        if (e.target.closest('.remove-accordion-tab')) return;
                        headerRows.forEach((otherRow) => {
                            const panel = otherRow.parentNode.querySelector('.accordion-panel');
                            if (otherRow === row) {
                                const expanded = row.getAttribute('aria-expanded') === 'true';
                                row.setAttribute('aria-expanded', !expanded);
                                row.classList.toggle('open', !expanded);
                                if (panel) panel.style.display = expanded ? 'none' : 'block';
                            } else {
                                otherRow.setAttribute('aria-expanded', 'false');
                                otherRow.classList.remove('open');
                                if (panel) panel.style.display = 'none';
                            }
                        });
                    });
                    // Allow keyboard accessibility
                    row.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            row.click();
                        }
                    });
                    // Remove tab logic
                    const removeBtn = row.querySelector('.remove-accordion-tab');
                    if (removeBtn) {
                        removeBtn.addEventListener('click', function (e) {
                            e.stopPropagation();
                            const item = row.closest('.accordion-item');
                            item.remove();
                            // Open the first accordion if any remain
                            const remainingRows = document.querySelectorAll('.accordion-header-row');
                            if (remainingRows.length > 0) {
                                remainingRows[0].setAttribute('aria-expanded', 'true');
                                remainingRows[0].classList.add('open');
                                const firstPanel = remainingRows[0].parentNode.querySelector('.accordion-panel');
                                if (firstPanel) firstPanel.style.display = 'block';
                            }
                        });
                    }
                });
                // Open the first accordion by default
                if (headerRows.length > 0) {
                    headerRows[0].setAttribute('aria-expanded', 'true');
                    headerRows[0].classList.add('open');
                    const firstPanel = headerRows[0].parentNode.querySelector('.accordion-panel');
                    if (firstPanel) firstPanel.style.display = 'block';
                }
            });

            // Add new accordion tab
            document.getElementById('add-accordion-tab').addEventListener('click', function () {
                var container = document.getElementById('accordion-container');
                var newIndex = container.querySelectorAll('.accordion-item').length;
                var isElementorProActive = <?php echo json_encode($this->is_elementor_active()); ?>;
                var savedSections = <?php echo json_encode($saved_sections); ?>;
                var sectionOptions = '<option value=""><?php _e('Select a section', 'ydtb-group-tabs'); ?></option>';
                for (var id in savedSections) {
                    sectionOptions += '<option value="' + id + '">' + savedSections[id] + '</option>';
                }

                var item = document.createElement('div');
                item.className = 'accordion-item';
                item.innerHTML = `
                    <div class="accordion-header-row" tabindex="0" aria-expanded="false">
                        <span class="accordion-title">New Tab</span>
                        <span class="chevron">&#9654;</span>
                        <span class="move-tab-buttons">
                            <button type="button" class="move-tab-up" title="<?php esc_attr_e('Move Up', 'ydtb-group-tabs'); ?>">&#8593;</button>
                            <button type="button" class="move-tab-down" title="<?php esc_attr_e('Move Down', 'ydtb-group-tabs'); ?>">&#8595;</button>
                        </span>
                        <button type="button" class="remove-accordion-tab" title="<?php esc_attr_e('Remove Tab', 'ydtb-group-tabs'); ?>">
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="white" aria-hidden="true" focusable="false">
                                <rect x="3" y="5.5" width="14" height="1.5" rx="0.75" fill="white"/>
                                <path d="M6.5 7.5V15.5M10 7.5V15.5M13.5 7.5V15.5M8.5 3.5H11.5C12.0523 3.5 12.5 3.94772 12.5 4.5V5.5H7.5V4.5C7.5 3.94772 7.94772 3.5 8.5 3.5Z" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                                <rect x="6.5" y="7.5" width="7" height="8" rx="1" fill="white" stroke="white" stroke-width="1"/>
                            </svg>
                        </button>
                    </div>
                    <div class="accordion-panel" style="display: none;">
                        <label>Name: <input type="text" name="ydtb_tabs[${newIndex}][name]" value="New Tab"></label>
                        <label>Slug: <input type="text" name="ydtb_tabs[${newIndex}][slug]" value="new-tab"></label>
                        <label>Type:
                            <select name="ydtb_tabs[${newIndex}][type]" class="tab-type-selector" data-index="${newIndex}">
                                <option value="url_redirect">URL Redirect</option>
                                <option value="shortcode">Shortcode</option>
                                ${isElementorProActive ? '<option value="saved_section">Saved Section</option>' : ''}
                            </select>
                        </label>
                        <div class="tab-type-fields" id="fields-${newIndex}"></div>
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
                    </div>
                `;
                container.appendChild(item);

                // Re-attach accordion logic
                var row = item.querySelector('.accordion-header-row');
                row.addEventListener('click', function (e) {
                    if (e.target.closest('.remove-accordion-tab')) return;
                    var headerRows = document.querySelectorAll('.accordion-header-row');
                    headerRows.forEach((otherRow) => {
                        const panel = otherRow.parentNode.querySelector('.accordion-panel');
                        if (otherRow === row) {
                            const expanded = row.getAttribute('aria-expanded') === 'true';
                            row.setAttribute('aria-expanded', !expanded);
                            row.classList.toggle('open', !expanded);
                            if (panel) panel.style.display = expanded ? 'none' : 'block';
                        } else {
                            otherRow.setAttribute('aria-expanded', 'false');
                            otherRow.classList.remove('open');
                            if (panel) panel.style.display = 'none';
                        }
                    });
                });
                row.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        row.click();
                    }
                });
                // Remove tab logic
                var removeBtn = row.querySelector('.remove-accordion-tab');
                if (removeBtn) {
                    removeBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        const item = row.closest('.accordion-item');
                        item.remove();
                        // Open the first accordion if any remain
                        const remainingRows = document.querySelectorAll('.accordion-header-row');
                        if (remainingRows.length > 0) {
                            remainingRows[0].setAttribute('aria-expanded', 'true');
                            remainingRows[0].classList.add('open');
                            const firstPanel = remainingRows[0].parentNode.querySelector('.accordion-panel');
                            if (firstPanel) firstPanel.style.display = 'block';
                        }
                    });
                }

                // Open the new accordion
                row.setAttribute('aria-expanded', 'true');
                row.classList.add('open');
                var panel = row.parentNode.querySelector('.accordion-panel');
                if (panel) panel.style.display = 'block';

                // Trigger the change event for all tab-type-selectors on initial load
                document.querySelectorAll('.tab-type-selector').forEach(function (selector) {
                    var event = new Event('change', { bubbles: true });
                    selector.dispatchEvent(event);
                });
            });

            document.querySelectorAll('.move-tab-up, .move-tab-down').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const isUp = btn.classList.contains('move-tab-up');
                    const item = btn.closest('.accordion-item');
                    const container = document.getElementById('accordion-container');
                    const allItems = Array.from(container.querySelectorAll('.accordion-item'));
                    const customItems = allItems.filter(i => i.querySelector('.remove-accordion-tab'));
                    const builtInPositions = allItems
                        .filter(i => !i.querySelector('.remove-accordion-tab'))
                        .map(i => parseInt(i.querySelector('span[style*="font-weight:bold"]').textContent, 10));
                    // Get current tab's position
                    const positionInput = item.querySelector('.tab-position-input');
                    let currentPos = parseInt(positionInput.value, 10);
                    // Find the next available position in the desired direction
                    let targetPos = currentPos + (isUp ? -1 : 1);
                    // Find the nearest built-in tab position in the direction
                    let limit = isUp
                        ? Math.max(0, ...builtInPositions.filter(p => p < currentPos))
                        : Math.min(9999, ...builtInPositions.filter(p => p > currentPos));
                    if (isUp && targetPos <= limit) targetPos = limit + 1;
                    if (!isUp && targetPos >= limit) targetPos = limit - 1;
                    // Recursive shift for collisions
                    function shiftTab(pos, dir) {
                        const colliding = customItems.find(i => parseInt(i.querySelector('.tab-position-input').value, 10) === pos);
                        if (colliding) {
                            shiftTab(pos + dir, dir);
                            colliding.querySelector('.tab-position-input').value = pos + dir;
                            // Update the header position number for the shifted tab
                            const headerPos = colliding.querySelector('span[style*="font-weight:bold"]');
                            if (headerPos) headerPos.textContent = pos + dir;
                        }
                    }
                    shiftTab(targetPos, isUp ? -1 : 1);
                    positionInput.value = targetPos;
                    // Update the header position number for the moved tab
                    const headerPos = item.querySelector('span[style*="font-weight:bold"]');
                    if (headerPos) headerPos.textContent = targetPos;
                    // Optionally, visually reorder the DOM (not required for saving, but nice for UX)
                    // Resort allItems by their .tab-position-input value
                    const sorted = allItems.slice().sort((a, b) => {
                        const aPos = parseInt(a.querySelector('.tab-position-input')?.value || a.querySelector('span[style*="font-weight:bold"]').textContent, 10);
                        const bPos = parseInt(b.querySelector('.tab-position-input')?.value || b.querySelector('span[style*="font-weight:bold"]').textContent, 10);
                        return aPos - bPos;
                    });
                    sorted.forEach(i => container.appendChild(i));
                });
            });
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
                $tab_position = isset($tab['position']) ? intval($tab['position']) : 9999;

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
                        'tab_position' => $tab_position,
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
