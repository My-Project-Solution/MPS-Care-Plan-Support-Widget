<?php
/**
 * Plugin Name: MPS Care Plan Support Widget
 * Plugin URI: https://myprojectsolution.com
 * Description: Adds the MPS Care Plan support widget to WordPress admin for authorized administrators, with secure identity verification for support chat and ticket creation.
 * Version: 2.0.0
 * Author: My Project Solution
 * Author URI: https://myprojectsolution.com
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register plugin settings.
 */
function mps_care_register_settings() {
    register_setting(
        'mps_care_settings_group',
        'mps_care_beacon_id',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        )
    );

    register_setting(
        'mps_care_settings_group',
        'mps_care_secure_key',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        )
    );
}
add_action( 'admin_init', 'mps_care_register_settings' );

/**
 * Add settings page under Settings.
 */
function mps_care_add_settings_page() {
    add_options_page(
        'MPS Care Plan',
        'MPS Care Plan',
        'manage_options',
        'mps-care-plan',
        'mps_care_render_settings_page'
    );
}
add_action( 'admin_menu', 'mps_care_add_settings_page' );

/**
 * Render settings page.
 */
function mps_care_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>MPS Care Plan</h1>
        <p>
            Configure the MPS Care Plan support widget for WordPress admin. This widget gives authorized administrators
            direct access to support chat and ticket creation with My Project Solution.
        </p>
        <p>
            Use this to connect your site to MPS Care Plan so your team can quickly report issues, request support,
            and create tickets with secure user verification.
        </p>

        <form method="post" action="options.php">
            <?php settings_fields( 'mps_care_settings_group' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="mps_care_beacon_id">MPS Care Plan Widget ID</label>
                    </th>
                    <td>
                        <input
                                type="text"
                                id="mps_care_beacon_id"
                                name="mps_care_beacon_id"
                                value="<?php echo esc_attr( get_option( 'mps_care_beacon_id', '' ) ); ?>"
                                class="regular-text"
                        />
                        <p class="description">
                            Enter the MPS Care Plan Beacon ID used for the MPS Care Plan support widget.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="mps_care_secure_key">MPS Care Plan Secure Key</label>
                    </th>
                    <td>
                        <input
                                type="password"
                                id="mps_care_secure_key"
                                name="mps_care_secure_key"
                                value="<?php echo esc_attr( get_option( 'mps_care_secure_key', '' ) ); ?>"
                                class="regular-text"
                                autocomplete="off"
                        />
                        <p class="description">
                            Enter the secure key for MPS Care Plan Secure Mode. This is used to verify administrator identity
                            so support chats and tickets are securely associated with the correct account.
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button( 'Save MPS Care Plan Settings' ); ?>
        </form>
    </div>
    <?php
}

/**
 * Output MPS Care Plan support widget in WP Admin footer.
 */
function mps_care_admin_widget_output() {
    // Only in WP Admin.
    if ( ! is_admin() ) {
        return;
    }

    // Must be logged in.
    if ( ! is_user_logged_in() ) {
        return;
    }

    $user = wp_get_current_user();

    // Must have administrator role.
    if ( empty( $user->roles ) || ! in_array( 'administrator', $user->roles, true ) ) {
        return;
    }

    // Require user email for Secure Mode.
    if ( empty( $user->user_email ) ) {
        return;
    }

    $beacon_id  = get_option( 'mps_care_beacon_id', '' );
    $secure_key = get_option( 'mps_care_secure_key', '' );

    if ( empty( $beacon_id ) || empty( $secure_key ) ) {
        return;
    }

    $signature = hash_hmac( 'sha256', $user->user_email, $secure_key );

    $user_name  = trim( $user->display_name );

    if ( empty( $user_name ) ) {
        $user_name = trim( $user->first_name . ' ' . $user->last_name );
    }

    if ( empty( $user_name ) ) {
        $user_name = $user->user_login;
    }
    ?>
    <script type="text/javascript">!function(e,t,n){function a(){var e=t.getElementsByTagName("script")[0],n=t.createElement("script");n.type="text/javascript",n.async=!0,n.src="https://beacon-v2.helpscout.net",e.parentNode.insertBefore(n,e)}if(e.Beacon=n=function(t,n,a){e.Beacon.readyQueue.push({method:t,options:n,data:a})},n.readyQueue=[],"complete"===t.readyState)return a();e.attachEvent?e.attachEvent("onload",a):e.addEventListener("load",a,!1)}(window,document,window.Beacon||function(){});</script>
    <script type="text/javascript">
        window.Beacon('init', <?php echo wp_json_encode( $beacon_id ); ?>);
        window.Beacon('identify', {
            name: <?php echo wp_json_encode( $user_name ); ?>,
            email: <?php echo wp_json_encode( $user->user_email ); ?>,
            signature: <?php echo wp_json_encode( $signature ); ?>
        });
    </script>
    <?php
}
add_action( 'admin_footer', 'mps_care_admin_widget_output' );