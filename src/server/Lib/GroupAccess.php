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
    }

    public function filter_group_join_button($button, $group)
    {
        // Only modify for private groups, not already a member, not invited, not pending
        if (
            isset($group->status) && $group->status === 'private'
            && empty($group->is_member)
            && empty($group->is_invited)
            && empty($group->is_pending)
        ) {
            // Get the group-specific signup URL and button text
            $signup_url = groups_get_groupmeta($group->id, 'ydtb_signup_url', true);
            $button_text = groups_get_groupmeta($group->id, 'ydtb_signup_button_text', true);
            if (empty($button_text)) {
                $button_text = __('Sign Up to Join', 'buddyboss');
            }
            if (!empty($signup_url)) {
                $button = array(
                    'id' => 'external_signup',
                    'component' => 'groups',
                    'must_be_logged_in' => false,
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


