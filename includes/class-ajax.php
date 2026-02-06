<?php
if ( ! defined('ABSPATH') ) exit;

class QRM_Ajax {

    public static function init() {
        add_action('wp_ajax_qrm_request_qr', [__CLASS__, 'request_qr']);
    }

    public static function request_qr() {

        check_ajax_referer('qrm_nonce', 'nonce');

        if ( ! is_user_logged_in() ) {
            wp_send_json_error(['message' => 'Not logged in']);
        }

        $user_id = get_current_user_id();

        // Check if request already exists
        $existing = get_posts([
            'post_type'      => 'qr_request',
            'posts_per_page' => 1,
            'meta_query'     => [
                ['key' => '_qrm_user_id', 'value' => $user_id],
            ]
        ]);

        if ( $existing ) {
            wp_send_json_error(['message' => 'Request already exists']);
        }

        $user = wp_get_current_user();

        $post_id = wp_insert_post([
            'post_type'   => 'qr_request',
            'post_title'  => 'QR Request – ' . $user->display_name,
            'post_status' => 'publish'
        ], true);

        if ( is_wp_error($post_id) ) {
            wp_send_json_error(['message' => 'Could not create request']);
        }

        update_post_meta($post_id, '_qrm_user_id', $user_id);
        update_post_meta($post_id, '_qrm_status', 'pending');
        update_post_meta($post_id, '_qrm_meet_qr', 0);
        update_post_meta($post_id, '_qrm_shop_qr', 0);

        wp_send_json_success(['message' => 'QR request submitted']);
    }

}
