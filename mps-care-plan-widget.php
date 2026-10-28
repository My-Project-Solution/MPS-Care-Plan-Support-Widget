<?php
/**
 * Plugin Name: MPS Care Plan Support Widget
 * Plugin URI: https://myprojectsolution.com/mps-care-plan
 * Update URI: https://github.com/My-Project-Solution/MPS-Care-Plan-Support-Widget
 * Description: Adds a Help Scout Beacon support widget to the WordPress admin for administrators, with Secure Mode identity verification for support chat and ticket creation, and an optional Get support page that sends tickets to the MPS Client Portal.
 * Version: 2.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * Author: My Project Solution
 * Author URI: https://myprojectsolution.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php';
require_once __DIR__ . '/includes/portal-tickets.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Check GitHub releases for updates. The repository is public, so no token is needed.
 */
$mps_care_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/My-Project-Solution/MPS-Care-Plan-Support-Widget/',
    __FILE__,
    'mps-care-plan-widget'
);

// Update from the zip built by the release workflow, not GitHub's auto-generated source
// tarball, whose folder name carries the tag and would break the plugin path. The regex
// pins the asset by name so any other asset added to a release can never be picked up.
$mps_care_update_checker->getVcsApi()->enableReleaseAssets( '/^mps-care-plan-widget\.zip$/' );

/**
 * Get the Beacon ID, preferring the MPS_CARE_BEACON_ID constant over the stored option.
 *
 * @return string
 */
function mps_care_get_beacon_id() {
    if ( defined( 'MPS_CARE_BEACON_ID' ) && MPS_CARE_BEACON_ID ) {
        return (string) MPS_CARE_BEACON_ID;
    }

    return (string) get_option( 'mps_care_beacon_id', '' );
}

/**
 * Get the Secure Mode key, preferring the MPS_CARE_SECURE_KEY constant over the stored option.
 *
 * @return string
 */
function mps_care_get_secure_key() {
    if ( defined( 'MPS_CARE_SECURE_KEY' ) && MPS_CARE_SECURE_KEY ) {
        return (string) MPS_CARE_SECURE_KEY;
    }

    return (string) get_option( 'mps_care_secure_key', '' );
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
            'sanitize_callback' => 'mps_care_sanitize_secure_key',
            'default'           => '',
        )
    );
}
add_action( 'admin_init', 'mps_care_register_settings' );

/**
 * Sanitize the Secure Mode key.
 *
 * The key is never rendered back into the form, so the field submits empty unless the user is
 * deliberately replacing it. An empty submission therefore means "keep the saved key" rather than
 * "clear it"; clearing requires the explicit checkbox. options.php verifies the nonce before this runs.
 *
 * @param string $value Submitted value.
 * @return string Key to store.
 */
function mps_care_sanitize_secure_key( $value ) {
    if ( isset( $_POST['mps_care_secure_key_clear'] ) ) {
        return '';
    }

    $value = sanitize_text_field( (string) $value );

    if ( '' === $value ) {
        return (string) get_option( 'mps_care_secure_key', '' );
    }

    return $value;
}

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

    $beacon_id_constant  = defined( 'MPS_CARE_BEACON_ID' ) && MPS_CARE_BEACON_ID;
    $secure_key_constant = defined( 'MPS_CARE_SECURE_KEY' ) && MPS_CARE_SECURE_KEY;
    $secure_key_is_set   = '' !== mps_care_get_secure_key();
    ?>
    <div class="wrap">
        <h1>MPS Care Plan</h1>
        <p>
            Configure the MPS Care Plan support widget for the WordPress admin. This widget gives administrators
            direct access to support chat and ticket creation with My Project Solution.
        </p>

        <form method="post" action="options.php">
            <?php settings_fields( 'mps_care_settings_group' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="mps_care_beacon_id">Beacon ID</label>
                    </th>
                    <td>
                        <?php if ( $beacon_id_constant ) : ?>
                            <p>
                                <strong>Defined in <code>wp-config.php</code></strong>
                                via <code>MPS_CARE_BEACON_ID</code>.
                            </p>
                        <?php else : ?>
                            <input
                                    type="text"
                                    id="mps_care_beacon_id"
                                    name="mps_care_beacon_id"
                                    value="<?php echo esc_attr( get_option( 'mps_care_beacon_id', '' ) ); ?>"
                                    class="regular-text"
                            />
                            <p class="description">
                                The Help Scout Beacon ID for the Beacon this site should load.
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="mps_care_secure_key">Secure Mode Key</label>
                    </th>
                    <td>
                        <?php if ( $secure_key_constant ) : ?>
                            <p>
                                <strong>Defined in <code>wp-config.php</code></strong>
                                via <code>MPS_CARE_SECURE_KEY</code>.
                            </p>
                        <?php else : ?>
                            <input
                                    type="password"
                                    id="mps_care_secure_key"
                                    name="mps_care_secure_key"
                                    value=""
                                    class="regular-text"
                                    autocomplete="new-password"
                                    placeholder="<?php echo $secure_key_is_set ? 'Saved &mdash; leave blank to keep' : 'Not set'; ?>"
                            />
                            <p class="description">
                                The Help Scout Beacon Secure Mode key. Used to sign each administrator's email address
                                so support conversations are tied to the right person.
                                <?php if ( $secure_key_is_set ) : ?>
                                    A key is saved. For security it is never displayed again &mdash; leave this field
                                    blank to keep it, or enter a new key to replace it.
                                <?php endif; ?>
                            </p>
                            <?php if ( $secure_key_is_set ) : ?>
                                <p>
                                    <label>
                                        <input type="checkbox" name="mps_care_secure_key_clear" value="1" />
                                        Remove the saved key
                                    </label>
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php mps_care_render_portal_settings_rows(); ?>
            </table>

            <?php submit_button( 'Save MPS Care Plan Settings' ); ?>
        </form>
    </div>
    <?php
}

/**
 * Output the support widget in the WP Admin footer.
 */
function mps_care_admin_widget_output() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $user = wp_get_current_user();

    // Must have administrator role.
    if ( empty( $user->roles ) || ! in_array( 'administrator', $user->roles, true ) ) {
        return;
    }

    // Required for Secure Mode.
    if ( empty( $user->user_email ) ) {
        return;
    }

    $beacon_id  = mps_care_get_beacon_id();
    $secure_key = mps_care_get_secure_key();

    if ( empty( $beacon_id ) || empty( $secure_key ) ) {
        return;
    }

    $signature = hash_hmac( 'sha256', $user->user_email, $secure_key );

    $user_name = trim( $user->display_name );

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
