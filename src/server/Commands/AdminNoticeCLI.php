<?php

namespace YDTBGroupTabs\Commands;

class AdminNoticeCLI extends \WP_CLI_Command
{
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Adds a string to the hide_admin_notice_strings option in the database.
     *
     * ## OPTIONS
     *
     * <string>
     * : The string to add to the hide_admin_notice_strings option.
     *
     * ## EXAMPLES
     *
     *     wp admin-notice add "This is a notice to hide"
     *
     * @when after_wp_load
     */
    // public function add($args, $assoc_args)
    // {
    //     $string = $args[0];
    //     $forbidden_message_strings = get_option('hide_admin_notice_strings', []);
    //     if (in_array($string, $forbidden_message_strings)) {
    //         \WP_CLI::warning("String already exists in hide_admin_notice_strings.");
    //     } else {
    //         $forbidden_message_strings[] = $string;
    //         update_option('hide_admin_notice_strings', $forbidden_message_strings);
    //         \WP_CLI::success("String added to hide_admin_notice_strings.");
    //     }
    // }

    // /**
    //  * Lists the current strings in the hide_admin_notice_strings option.
    //  *
    //  * ## EXAMPLES
    //  *
    //  *     wp admin-notice list
    //  *
    //  * @when after_wp_load
    //  */
    // public function list($args, $assoc_args)
    // {
    //     $forbidden_message_strings = get_option('hide_admin_notice_strings', []);
    //     if (empty($forbidden_message_strings)) {
    //         \WP_CLI::log("No strings found in hide_admin_notice_strings.");
    //     } else {
    //         foreach ($forbidden_message_strings as $index => $string) {
    //             \WP_CLI::log("[$index] $string");
    //         }
    //     }
    // }

    // /**
    //  * Removes a string from the hide_admin_notice_strings option based on its order (number).
    //  * The number corresponds to the index of the string in the array stored in the database.
    //  * you can review the order with the list command.
    //  *
    //  * ## OPTIONS
    //  *
    //  * <index>
    //  * : The index of the string to remove from the hide_admin_notice_strings option.
    //  *
    //  * ## EXAMPLES
    //  *
    //  *     wp admin-notice remove 0
    //  *
    //  * @when after_wp_load
    //  */
    // public function remove($args, $assoc_args)
    // {
    //     $index = intval($args[0]);
    //     $forbidden_message_strings = get_option('hide_admin_notice_strings', []);
    //     if (isset($forbidden_message_strings[$index])) {
    //         unset($forbidden_message_strings[$index]);
    //         $forbidden_message_strings = array_values($forbidden_message_strings); // Reindex array
    //         update_option('hide_admin_notice_strings', $forbidden_message_strings);
    //         \WP_CLI::success("String removed from hide_admin_notice_strings.");
    //     } else {
    //         \WP_CLI::error("Invalid index provided.");
    //     }
    // }
}
