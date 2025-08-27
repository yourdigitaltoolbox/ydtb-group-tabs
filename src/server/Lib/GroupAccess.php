<?php

class GroupAccess extends BP_Group_Extension
{
    public function __construct()
    {
        $args = array(
            'slug' => 'signup-url',
            'name' => 'Signup URL',
            'show_tab' => "noone",
        );
        parent::init($args);

        add_filter('bp_get_group_join_button', [$this, 'filter_group_join_button'], 20, 2);
        add_filter('bp_get_group_permalink', [$this, 'filter_group_permalink'], 20, 1);
        add_filter('bp_get_group_avatar', [$this, 'filter_group_avatar'], 20, 1);
        add_filter('bp_get_group_request_link', [$this, 'filter_group_request_link'], 20, 1);

        // Add action to intercept redirects to login page for non-logged-in users
        add_action('template_redirect', [$this, 'redirect_non_logged_in_users'], 5);

        // Filter the authentication redirect for groups
        add_filter('bp_authenticate_redirect', [$this, 'filter_authenticate_redirect'], 10, 3);

        // Override BuddyPress private content redirect to login
        add_action('bp_core_no_access', [$this, 'override_private_group_redirect'], 5);

        // Ensure group button is visible for non-logged-in users
        add_filter('bp_group_join_button', [$this, 'ensure_button_for_non_logged_in'], 10, 1);
    }
    public function filter_group_join_button($button, $group)
    {
        // Modify for private groups and ensure button is displayed to both logged-in and non-logged-in users
        if (isset($group->status) && $group->status === 'private') {
            // Get the group-specific signup URL and button text
            $signup_url = groups_get_groupmeta($group->id, 'ydtb_signup_url', true);
            $button_text = groups_get_groupmeta($group->id, 'ydtb_signup_button_text', true);

            if (empty($button_text)) {
                $button_text = __('Sign Up to Join', 'buddyboss');
            }

            // Only modify the button if we have a signup URL
            if (!empty($signup_url)) {
                // If user is already a member, invited, or has a pending request, don't change the button
                if (!empty($group->is_member) || !empty($group->is_invited) || !empty($group->is_pending)) {
                    return $button;
                }

                // Create a button for all users, including non-logged-in users
                $button = array(
                    'id' => 'external_signup',
                    'component' => 'groups',
                    'must_be_logged_in' => false, // Allow non-logged-in users to see this button
                    'block_self' => false,
                    'wrapper_class' => 'group-button ' . $group->status,
                    'wrapper_id' => 'groupbutton-' . $group->id,
                    'link_href' => $signup_url,
                    'link_text' => $button_text,
                    'link_class' => 'group-button external-signup',
                    'button_attr' => array(
                        'onclick' => "window.location.href='" . esc_url($signup_url) . "'; return false;",
                        'type' => 'button',
                    ),
                );
            }
        }
        return $button;
    }

    /**
     * Filter the group permalink to redirect to custom signup URL for non-members
     * 
     * @param string $permalink The group's permalink
     * @return string Modified permalink if conditions are met
     */
    public function filter_group_permalink($permalink)
    {
        // Get current group ID
        $group_id = bp_get_current_group_id();
        if (!$group_id) {
            // If we can't get the current group ID directly, try to get it from the URL
            global $groups_template;
            if (isset($groups_template->group->id)) {
                $group_id = $groups_template->group->id;
            }
        }

        if ($group_id) {
            // Get group object
            $group = groups_get_group($group_id);

            // Only modify for private groups, not already a member, not invited, not pending
            if (
                isset($group->status) && $group->status === 'private'
                && !groups_is_user_member(get_current_user_id(), $group_id)
                && !groups_is_user_invited(get_current_user_id(), $group_id)
                && !groups_check_user_has_invite(get_current_user_id(), $group_id)
            ) {
                // Get the group-specific signup URL
                $signup_url = groups_get_groupmeta($group_id, 'ydtb_signup_url', true);

                if (!empty($signup_url)) {
                    return $signup_url;
                }
            }
        }

        return $permalink;
    }

    /**
     * Filter the group avatar to modify the link behavior when clicked
     * 
     * @param string $avatar The HTML for the group avatar
     * @return string Modified avatar HTML with custom onclick behavior if needed
     */
    public function filter_group_avatar($avatar)
    {
        // Get current group ID
        $group_id = bp_get_group_id();
        if (!$group_id) {
            global $groups_template;
            if (isset($groups_template->group->id)) {
                $group_id = $groups_template->group->id;
            }
        }

        if ($group_id) {
            // Get group object
            $group = groups_get_group($group_id);

            // Only modify for private groups, not already a member, not invited, not pending
            if (
                isset($group->status) && $group->status === 'private'
                && !groups_is_user_member(get_current_user_id(), $group_id)
                && !groups_is_user_invited(get_current_user_id(), $group_id)
                && !groups_check_user_has_invite(get_current_user_id(), $group_id)
            ) {
                // Get the group-specific signup URL
                $signup_url = groups_get_groupmeta($group_id, 'ydtb_signup_url', true);

                if (!empty($signup_url)) {
                    // Modify the avatar link to include our custom onclick
                    $avatar = preg_replace(
                        '/<a\s+([^>]*)>/i',
                        '<a $1 onclick="window.location.href=\'' . esc_url($signup_url) . '\'; return false;">',
                        $avatar
                    );
                }
            }
        }

        return $avatar;
    }

    /**
     * Filter the group request link (used when requesting access to a private group)
     * 
     * @param string $request_link The HTML link for the group request
     * @return string Modified link if custom signup URL is set
     */
    public function filter_group_request_link($request_link)
    {
        // Get current group ID
        $group_id = bp_get_group_id();
        if (!$group_id) {
            global $groups_template;
            if (isset($groups_template->group->id)) {
                $group_id = $groups_template->group->id;
            }
        }

        if ($group_id) {
            // Get the group-specific signup URL
            $signup_url = groups_get_groupmeta($group_id, 'ydtb_signup_url', true);

            if (!empty($signup_url)) {
                // Replace the href attribute in the link with our custom URL
                $request_link = preg_replace(
                    '/href=(["\'])(?:[^"\']+)\\1/i',
                    'href="' . esc_url($signup_url) . '"',
                    $request_link
                );
            }
        }

        return $request_link;
    }

    /**
     * Redirect non-logged-in users to the signup URL instead of the login page
     * when they try to access a private group
     */
    public function redirect_non_logged_in_users()
    {
        // Only proceed if not logged in and we're on a group page
        // But we want to avoid redirecting from the group directory listing
        if (!is_user_logged_in() && bp_is_group() && !bp_is_groups_directory()) {
            $group_id = bp_get_current_group_id();

            if ($group_id) {
                $group = groups_get_group($group_id);

                // Check if it's a private group
                if (isset($group->status) && $group->status === 'private') {
                    // Get the group-specific signup URL
                    $signup_url = groups_get_groupmeta($group_id, 'ydtb_signup_url', true);

                    if (!empty($signup_url)) {
                        // Redirect to the signup URL instead of login, but only if trying to access group content
                        // not just viewing the groups listing
                        wp_redirect($signup_url);
                        exit;
                    }
                }
            }
        }
    }    /**
         * Filter the authentication redirect for group pages
         * 
         * @param string $redirect_to URL to redirect to
         * @param string $redirect_from URL the user is coming from
         * @param object $user Logged in user object
         * @return string Modified redirect URL if conditions are met
         */
    public function filter_authenticate_redirect($redirect_to, $redirect_from, $user)
    {
        // Check if the user is coming from a BuddyPress group page
        if (strpos($redirect_from, '/' . bp_get_groups_root_slug() . '/') !== false) {
            // Extract the group slug from the URL
            $url_parts = parse_url($redirect_from);
            $path_parts = explode('/', trim($url_parts['path'], '/'));

            // Find the group slug in the path
            $group_slug = '';
            $groups_key = array_search(bp_get_groups_root_slug(), $path_parts);
            if ($groups_key !== false && isset($path_parts[$groups_key + 1])) {
                $group_slug = $path_parts[$groups_key + 1];
            }

            if (!empty($group_slug)) {
                // Get the group ID from the slug
                $group_id = BP_Groups_Group::get_id_from_slug($group_slug);

                if ($group_id) {
                    // Get the group-specific signup URL
                    $signup_url = groups_get_groupmeta($group_id, 'ydtb_signup_url', true);

                    if (!empty($signup_url)) {
                        // Redirect to the signup URL instead of login
                        return $signup_url;
                    }
                }
            }
        }

        return $redirect_to;
    }

    /**
     * Override the BuddyPress "No Access" screen that requires login for private content
     */
    public function override_private_group_redirect()
    {
        // Only handle this for group pages - skip directory pages to show buttons
        if (bp_is_group() && !bp_is_groups_directory()) {
            $group_id = bp_get_current_group_id();

            if ($group_id) {
                // Get the group-specific signup URL
                $signup_url = groups_get_groupmeta($group_id, 'ydtb_signup_url', true);

                if (!empty($signup_url)) {
                    // Redirect to the signup URL instead of showing the "must log in" page
                    wp_redirect($signup_url);
                    exit;
                }
            }
        }
    }

    /**
     * Ensure join button is visible for non-logged-in users in group directory
     * 
     * @param array $button_args Button arguments
     * @return array Modified button arguments
     */
    public function ensure_button_for_non_logged_in($button_args)
    {
        // If there's a custom signup URL and this is a private group
        if (!empty($button_args['group']) && isset($button_args['group']->status) && $button_args['group']->status === 'private') {
            $group_id = $button_args['group']->id;
            $signup_url = groups_get_groupmeta($group_id, 'ydtb_signup_url', true);
            $button_text = groups_get_groupmeta($group_id, 'ydtb_signup_button_text', true);

            if (empty($button_text)) {
                $button_text = __('Sign Up to Join', 'buddyboss');
            }

            if (!empty($signup_url)) {
                // Make sure button is always visible
                $button_args['must_be_logged_in'] = false;
                $button_args['link_href'] = $signup_url;
                $button_args['link_text'] = $button_text;

                if (isset($button_args['button_attr'])) {
                    $button_args['button_attr']['onclick'] = "window.location.href='" . esc_url($signup_url) . "'; return false;";
                }
            }
        }

        return $button_args;
    }
    public function create_screen($group_id = null)
    {
    }
    public function create_screen_save($group_id = null)
    {
    }

    public function edit_screen($group_id = null)
    {
        $signup_url = groups_get_groupmeta($group_id, 'ydtb_signup_url', true);
        $button_text = groups_get_groupmeta($group_id, 'ydtb_signup_button_text', true);
        if (empty($button_text)) {
            $button_text = __('Sign Up to Join', 'buddyboss');
        }
        ?>
        <h3>YDTB External Signup</h3>
        <p>
            If you provide a URL and button text below, the default group join action will be replaced. Instead of the usual
            request access button for private groups, users will be redirected to your specified page when they click the
            button. This is useful for sending users to a sign-up or sales page, where you can set up your own automation to add
            them to the group after they complete your process.<br><br>
            <strong>Please note:</strong> The automation for adding users to the group after signup is up to you. This setting
            only changes the button’s behavior.
        </p>
        <label for="ydtb_signup_url">External Signup URL (optional):</label>
        <input type="url" name="ydtb_signup_url" id="ydtb_signup_url" value="<?php echo esc_attr($signup_url); ?>"
            style="width:100%;" />
        <br><br>
        <label for="ydtb_signup_button_text">Signup Button Text (optional):</label>
        <input type="text" name="ydtb_signup_button_text" id="ydtb_signup_button_text"
            value="<?php echo esc_attr($button_text); ?>" style="width:100%;" />
        <?php
    }

    public function edit_screen_save($group_id = null)
    {
        if (isset($_POST['ydtb_signup_url'])) {
            groups_update_groupmeta($group_id, 'ydtb_signup_url', esc_url_raw($_POST['ydtb_signup_url']));
        }
        if (isset($_POST['ydtb_signup_button_text'])) {
            groups_update_groupmeta($group_id, 'ydtb_signup_button_text', sanitize_text_field($_POST['ydtb_signup_button_text']));
        }
    }
}


