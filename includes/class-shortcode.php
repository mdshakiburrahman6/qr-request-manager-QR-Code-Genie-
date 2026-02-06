<?php
if ( ! defined('ABSPATH') ) exit;

class QRM_Shortcode {

    public static function init() {
        add_shortcode('qrm_qr_section', [__CLASS__, 'render']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
    }

    public static function enqueue() {

        if ( ! is_user_logged_in() ) return;

        wp_enqueue_style(
            'qrm-frontend',
            QRM_URL . 'assets/css/frontend.css',
            [],
            '1.0'
        );

        wp_enqueue_script(
            'qrm-frontend',
            QRM_URL . 'assets/js/frontend.js',
            ['jquery'],
            '1.0',
            true
        );

        wp_localize_script('qrm-frontend', 'qrm_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('qrm_nonce')
        ]);
    }

    /** SHORTCODE OUTPUT */
    public static function render() {

        if ( ! is_user_logged_in() ) {
            return '<p>Please login.</p>';
        }

        $user_id = get_current_user_id();
        $user    = wp_get_current_user();
        $req     = self::get_request($user_id);

        // No request yet
        if ( ! $req ) {

            ob_start();
            ?>
            <div class="qrm-card qrm-empty">

                <div class="qrm-header">
                    <div class="qrm-user">
                        👤 <?php echo esc_html($user->display_name); ?>
                    </div>
                </div>

                <div class="qrm-body" style="text-align:center">
                    <p>No QR request found.</p>

                    <button class="qrm-request-all">
                        Request QR Codes
                    </button>
                </div>

            </div>
            <?php
            return ob_get_clean();
        }


        $status  = get_post_meta($req->ID, '_qrm_status', true);
        $meet_qr = get_post_meta($req->ID, '_qrm_meet_qr', true);
        $shop_qr = get_post_meta($req->ID, '_qrm_shop_qr', true);

        ob_start();
        ?>
        <div class="qrm-card">

            <div class="qrm-header">
                <div class="qrm-user">
                    👤 <?php echo esc_html($user->display_name); ?>
                </div>

                <span class="qrm-status status-<?php echo esc_attr($status); ?>">
                    <?php echo esc_html(ucfirst($status)); ?>
                </span>
            </div>

            <div class="qrm-body">

                <div class="qrm-qr-grid">

                    <!-- Meet QR -->
                    <div class="qrm-qr-box">
                        <h4>Meet QR Code</h4>

                        <?php if ( $meet_qr ): ?>
                            <?php $url = wp_get_attachment_url($meet_qr); ?>
                            <div class="qrm-image-wrap">
                                <img src="<?php echo esc_url($url); ?>" alt="Meet QR">
                            </div>
                            <a class="qrm-download" href="<?php echo esc_url($url); ?>" download>
                                Download
                            </a>
                        <?php else: ?>
                            <p class="qrm-pending">Not ready yet</p>
                        <?php endif; ?>
                    </div>

                    <!-- Shop QR -->
                    <div class="qrm-qr-box">
                        <h4>Shop QR Code</h4>

                        <?php if ( $shop_qr ): ?>
                            <?php $url = wp_get_attachment_url($shop_qr); ?>
                            <div class="qrm-image-wrap">
                                <img src="<?php echo esc_url($url); ?>" alt="Shop QR">
                            </div>
                            <a class="qrm-download" href="<?php echo esc_url($url); ?>" download>
                                Download
                            </a>
                        <?php else: ?>
                            <p class="qrm-pending">Not ready yet</p>
                        <?php endif; ?>
                    </div>

                </div>

            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /** GET REQUEST */
    private static function get_request($user_id) {

        $req = get_posts([
            'post_type'      => 'qr_request',
            'posts_per_page' => 1,
            'meta_query'     => [
                ['key' => '_qrm_user_id', 'value' => $user_id],
            ]
        ]);

        return $req ? $req[0] : false;
    }
}
