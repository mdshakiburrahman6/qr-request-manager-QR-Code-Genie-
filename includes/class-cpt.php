<?php
if ( ! defined('ABSPATH') ) exit;

class QRM_CPT {

    public static function init() {
        add_action('init', [__CLASS__, 'register_cpt']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post', [__CLASS__, 'save_meta']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
        add_action('admin_menu', [__CLASS__, 'remove_add_new_menu'], 999);
        add_action('admin_init', [__CLASS__, 'block_manual_add']);
        add_action('admin_menu', [__CLASS__, 'add_menu_badge']);

        // Add custom column to admin list
        add_filter('manage_qr_request_posts_columns', function ($columns) {

            $new = [];

            foreach ($columns as $key => $value) {
                $new[$key] = $value;

                if ($key === 'title') {
                    $new['qrm_status'] = 'Status';
                }
            }

            return $new;
        });

        //  Display status badge in custom column
        add_action('manage_qr_request_posts_custom_column', function ($column, $post_id) {

            if ($column === 'qrm_status') {

                $status = get_post_meta($post_id, '_qrm_status', true);

                if (! $status) {
                    echo '—';
                    return;
                }

                $label = ucfirst($status);
                $class = 'qrm-status-badge status-' . esc_attr($status);

                echo '<span class="' . $class . '">' . esc_html($label) . '</span>';
            }

        }, 10, 2);
        
        // Admin styles for status badges
        add_action('admin_head', function () {
            ?>
            <style>
                .qrm-status-badge {
                    padding: 4px 10px;
                    border-radius: 12px;
                    font-weight: 600;
                    font-size: 12px;
                    display: inline-block;
                }
                .status-pending {
                    background: #fff3cd;
                    color: #856404;
                }
                .status-progress {
                    background: #e7f1ff;
                    color: #084298;
                }
                .status-completed {
                    background: #d1e7dd;
                    color: #0f5132;
                }
            </style>
            <?php
        });

        // Add dropdown filter to admin list
        add_action('restrict_manage_posts', function () {

            global $typenow;

            if ($typenow !== 'qr_request') return;

            $current = $_GET['qrm_status'] ?? '';
            ?>
            <select name="qrm_status">
                <option value="">All Statuses</option>
                <option value="pending" <?php selected($current,'pending'); ?>>Pending</option>
                <option value="progress" <?php selected($current,'progress'); ?>>In Progress</option>
                <option value="completed" <?php selected($current,'completed'); ?>>Completed</option>
            </select>
            <?php
        });

        // Filter posts based on selected status
        add_action('pre_get_posts', function ($query) {

            if (
                ! is_admin() ||
                ! $query->is_main_query() ||
                $query->get('post_type') !== 'qr_request'
            ) {
                return;
            }

            if (! empty($_GET['qrm_status'])) {
                $query->set('meta_query', [
                    [
                        'key'   => '_qrm_status',
                        'value' => sanitize_text_field($_GET['qrm_status']),
                    ]
                ]);
            }
        });

        // Change post title to username in admin list
        add_filter('the_title', function ($title, $post_id) {

            if ( ! is_admin() ) {
                return $title;
            }

            if ( get_post_type($post_id) !== 'qr_request' ) {
                return $title;
            }

            // Get user ID from meta
            $user_id = get_post_meta($post_id, '_qrm_user_id', true);

            if ( ! $user_id ) {
                return $title;
            }

            $user = get_user_by('id', $user_id);

            if ( ! $user ) {
                return $title;
            }

            // Only username in admin list
            return $user->display_name;

        }, 10, 2);

        // Change "No QR Requests found" message in admin list
        add_filter('manage_qr_request_posts_columns', function ($cols) {
            $cols['title'] = 'User';
            return $cols;
        });




    }

    // This removes the "Add New" button from the admin menu for our CPT
    public static function remove_add_new_menu() {
        remove_submenu_page(
            'edit.php?post_type=qr_request',
            'post-new.php?post_type=qr_request'
        );
    }

    // This is an extra safety measure to prevent manual addition of QR requests via URL manipulation
    public static function block_manual_add() {

        if (
            isset($_GET['post_type']) &&
            $_GET['post_type'] === 'qr_request' &&
            strpos($_SERVER['REQUEST_URI'], 'post-new.php') !== false
        ) {
            wp_die('You are not allowed to create QR requests manually.');
        }
    }

    // Register Custom Post Type
    public static function register_cpt() {

        register_post_type('qr_request', [
            'labels' => [
                'name'          => 'QR Requests',
                'singular_name' => 'QR Request',
                'menu_name'     => 'QR Requests',
                'add_new_item'  => 'Add QR Request',
                'edit_item'     => 'Edit QR Request',
            ],

            'public'        => false,
            'show_ui'       => true,

            // THIS IS THE KEY LINE
            'show_in_menu'  => true,

            'menu_icon'     => 'dashicons-controls-repeat',
            'supports'      => ['title'],
            'capability_type' => 'post',
            'capabilities'  => [
                'create_posts' => 'do_not_allow',
            ],
            'map_meta_cap'  => true,
        ]);
    }

    // Get request by user ID
    public static function add_meta_boxes() {

        add_meta_box(
            'qrm_details',
            'QR Request Details',
            [__CLASS__, 'meta_box_html'],
            'qr_request',
            'normal',
            'default'
        );
    }

    // Meta box HTML
    public static function meta_box_html($post) {

        $status  = get_post_meta($post->ID, '_qrm_status', true);
        $meet_qr = get_post_meta($post->ID, '_qrm_meet_qr', true);
        $shop_qr = get_post_meta($post->ID, '_qrm_shop_qr', true);

        wp_nonce_field('qrm_save_meta', 'qrm_nonce');
        wp_enqueue_media();
        ?>

        <p>
            <label>Status</label><br>
            <select name="qrm_status">
                <option value="pending"  <?php selected($status,'pending'); ?>>Pending</option>
                <option value="progress" <?php selected($status,'progress'); ?>>In Progress</option>
                <option value="completed"<?php selected($status,'completed'); ?>>Completed</option>
            </select>
        </p>

        <p>
            <label>Meet QR Image</label><br>

            <div class="qrm-preview" id="qrm-meet-preview">
                <?php if ( $meet_qr ): ?>
                    <?php echo wp_get_attachment_image($meet_qr, 'medium'); ?>
                <?php endif; ?>
            </div>

            <input type="hidden" name="qrm_meet_qr" id="qrm_meet_qr" value="<?php echo esc_attr($meet_qr); ?>">

            <button type="button"
                class="button qrm-upload"
                data-target="qrm_meet_qr"
                data-preview="qrm-meet-preview">
                <?php echo $meet_qr ? 'Replace Image' : 'Upload Image'; ?>
            </button>

            <?php if ( $meet_qr ): ?>
                <button type="button"
                    class="button button-link-delete qrm-remove"
                    data-target="qrm_meet_qr"
                    data-preview="qrm-meet-preview">
                    Remove Image
                </button>
            <?php endif; ?>
        </p>


        <p>
            <label>Shop QR Image</label><br>

            <div class="qrm-preview" id="qrm-shop-preview">
                <?php if ( $shop_qr ): ?>
                    <?php echo wp_get_attachment_image($shop_qr, 'medium'); ?>
                <?php endif; ?>
            </div>

            <input type="hidden" name="qrm_shop_qr" id="qrm_shop_qr" value="<?php echo esc_attr($shop_qr); ?>">

            <button type="button"
                class="button qrm-upload"
                data-target="qrm_shop_qr"
                data-preview="qrm-shop-preview">
                <?php echo $shop_qr ? 'Replace Image' : 'Upload Image'; ?>
            </button>

            <?php if ( $shop_qr ): ?>
                <button type="button"
                    class="button button-link-delete qrm-remove"
                    data-target="qrm_shop_qr"
                    data-preview="qrm-shop-preview">
                    Remove Image
                </button>
            <?php endif; ?>
        </p>



        <?php
    }

    // Save meta box data
    public static function save_meta($post_id) {

        if ( get_post_type($post_id) !== 'qr_request' ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;

        if ( ! isset($_POST['qrm_nonce']) ||
             ! wp_verify_nonce($_POST['qrm_nonce'], 'qrm_save_meta') ) {
            return;
        }

        if ( ! current_user_can('manage_options') ) return;

        $meet_qr = intval($_POST['qrm_meet_qr'] ?? 0);
        $shop_qr = intval($_POST['qrm_shop_qr'] ?? 0);
        $status  = sanitize_text_field($_POST['qrm_status'] ?? 'pending');

        // AUTO COMPLETE RULE
        if ( $meet_qr && $shop_qr ) {
            $status = 'completed';
        }

        // FORCE PREVENTION (extra safety)
        if ( $status === 'completed' && ( ! $meet_qr || ! $shop_qr ) ) {
            $status = 'progress';
        }

        update_post_meta($post_id, '_qrm_meet_qr', $meet_qr);
        update_post_meta($post_id, '_qrm_shop_qr', $shop_qr);
        update_post_meta($post_id, '_qrm_status', $status);


    }

    // Admin assets for media uploader
    public static function admin_assets($hook) {

        global $post;

        if (
            ($hook === 'post.php' || $hook === 'post-new.php') &&
            isset($post->post_type) &&
            $post->post_type === 'qr_request'
        ) {
            wp_enqueue_script(
                'qrm-admin',
                QRM_URL . 'assets/js/admin.js',
                ['jquery'],
                '1.0',
                true
            );
        }
    }

    // Get request by user ID
    public static function get_pending_count() {

        $count = get_posts([
            'post_type'      => 'qr_request',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => '_qrm_status',
                    'value'   => ['pending', 'progress'],
                    'compare' => 'IN',
                ]
            ],
            'fields' => 'ids'
        ]);

        return count($count);
    }
    
    // Add badge to menu
    public static function add_menu_badge() {

        global $menu;

        $count = self::get_pending_count();

        if ( $count <= 0 ) return;

        foreach ( $menu as $key => $item ) {

            if ( $item[2] === 'edit.php?post_type=qr_request' ) {

                $menu[$key][0] .=
                    ' <span class="awaiting-mod count-' . $count . '">' .
                    '<span class="pending-count">' . $count . '</span>' .
                    '</span>';

                break;
            }
        }
    }
    


}
