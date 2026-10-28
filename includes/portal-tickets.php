<?php
/**
 * Send support tickets to the MPS Client Portal.
 *
 * Adds a "Get support" page to the WordPress admin for administrators. The form posts
 * the ticket to the portal's POST /api/v1/tickets with this site's key, along with the
 * site's WordPress, PHP, theme, and active plugin details so MPS does not have to ask.
 *
 * Nothing here runs until a site key is saved: without one the page is not added.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const MPS_CARE_DEFAULT_PORTAL_URL = 'https://portal.myprojectsolution.com';

/**
 * Get the portal URL, preferring the MPS_CARE_PORTAL_URL constant over the stored option.
 *
 * @return string URL without a trailing slash.
 */
function mps_care_get_portal_url() {
    if ( defined( 'MPS_CARE_PORTAL_URL' ) && MPS_CARE_PORTAL_URL ) {
        $url = (string) MPS_CARE_PORTAL_URL;
    } else {
        $url = (string) get_option( 'mps_care_portal_url', '' );
    }

    if ( '' === $url ) {
        $url = MPS_CARE_DEFAULT_PORTAL_URL;
    }

    return untrailingslashit( $url );
}

/**
 * Get the portal site key, preferring the MPS_CARE_SITE_KEY constant over the stored option.
 *
 * @return string
 */
function mps_care_get_site_key() {
    if ( defined( 'MPS_CARE_SITE_KEY' ) && MPS_CARE_SITE_KEY ) {
        return (string) MPS_CARE_SITE_KEY;
    }

    return (string) get_option( 'mps_care_site_key', '' );
}

/**
 * Whether the current user may send tickets. Same rule as the Beacon: administrators only.
 *
 * @return bool
 */
function mps_care_user_can_send_tickets() {
    $user = wp_get_current_user();

    return ! empty( $user->roles ) && in_array( 'administrator', $user->roles, true );
}

/**
 * Register the portal settings on the existing MPS Care Plan settings page.
 */
function mps_care_register_portal_settings() {
    register_setting(
        'mps_care_settings_group',
        'mps_care_portal_url',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'mps_care_sanitize_portal_url',
            'default'           => '',
        )
    );

    register_setting(
        'mps_care_settings_group',
        'mps_care_site_key',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'mps_care_sanitize_site_key',
            'default'           => '',
        )
    );
}
add_action( 'admin_init', 'mps_care_register_portal_settings' );

/**
 * Sanitize the portal URL. Only https is accepted, so the site key never travels in clear text.
 *
 * @param string $value Submitted value.
 * @return string URL to store, or empty to use the default.
 */
function mps_care_sanitize_portal_url( $value ) {
    $value = trim( (string) $value );

    if ( '' === $value ) {
        return '';
    }

    $url = esc_url_raw( $value, array( 'https' ) );

    if ( '' === $url ) {
        add_settings_error( 'mps_care_portal_url', 'mps_care_portal_url', 'The portal URL must start with https://. The previous URL was kept.' );

        return (string) get_option( 'mps_care_portal_url', '' );
    }

    return untrailingslashit( $url );
}

/**
 * Sanitize the site key. Like the Secure Mode key it is never shown again, so an empty
 * submission keeps the saved key and clearing it needs the explicit checkbox.
 *
 * @param string $value Submitted value.
 * @return string Key to store.
 */
function mps_care_sanitize_site_key( $value ) {
    if ( isset( $_POST['mps_care_site_key_clear'] ) ) {
        return '';
    }

    $value = sanitize_text_field( (string) $value );

    if ( '' === $value ) {
        return (string) get_option( 'mps_care_site_key', '' );
    }

    return $value;
}

/**
 * Render the portal rows inside the settings page table.
 */
function mps_care_render_portal_settings_rows() {
    $url_constant    = defined( 'MPS_CARE_PORTAL_URL' ) && MPS_CARE_PORTAL_URL;
    $key_constant    = defined( 'MPS_CARE_SITE_KEY' ) && MPS_CARE_SITE_KEY;
    $key_is_set      = '' !== mps_care_get_site_key();
    ?>
    <tr>
        <th scope="row">
            <label for="mps_care_site_key">Portal site key</label>
        </th>
        <td>
            <?php if ( $key_constant ) : ?>
                <p>
                    <strong>Defined in <code>wp-config.php</code></strong>
                    via <code>MPS_CARE_SITE_KEY</code>.
                </p>
            <?php else : ?>
                <input
                        type="password"
                        id="mps_care_site_key"
                        name="mps_care_site_key"
                        value=""
                        class="regular-text"
                        autocomplete="new-password"
                        placeholder="<?php echo $key_is_set ? 'Saved, leave blank to keep' : 'Not set'; ?>"
                />
                <p class="description">
                    The key My Project Solution issued for this site in the MPS Client Portal. With a key saved,
                    administrators get a <strong>Get support</strong> page for sending tickets to MPS.
                    <?php if ( $key_is_set ) : ?>
                        A key is saved. For security it is never displayed again: leave this field blank to keep it,
                        or enter a new key to replace it.
                    <?php endif; ?>
                </p>
                <?php if ( $key_is_set ) : ?>
                    <p>
                        <label>
                            <input type="checkbox" name="mps_care_site_key_clear" value="1" />
                            Remove the saved key
                        </label>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="mps_care_portal_url">Portal URL</label>
        </th>
        <td>
            <?php if ( $url_constant ) : ?>
                <p>
                    <strong>Defined in <code>wp-config.php</code></strong>
                    via <code>MPS_CARE_PORTAL_URL</code>.
                </p>
            <?php else : ?>
                <input
                        type="url"
                        id="mps_care_portal_url"
                        name="mps_care_portal_url"
                        value="<?php echo esc_attr( get_option( 'mps_care_portal_url', '' ) ); ?>"
                        class="regular-text"
                        placeholder="<?php echo esc_attr( MPS_CARE_DEFAULT_PORTAL_URL ); ?>"
                />
                <p class="description">
                    Leave blank to use <?php echo esc_html( MPS_CARE_DEFAULT_PORTAL_URL ); ?>.
                </p>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}

/**
 * Add the "Get support" page under Dashboard once a site key is saved.
 */
function mps_care_add_support_page() {
    if ( '' === mps_care_get_site_key() || ! mps_care_user_can_send_tickets() ) {
        return;
    }

    add_dashboard_page(
        'Get support',
        'Get support',
        'manage_options',
        'mps-care-support',
        'mps_care_render_support_page'
    );
}
add_action( 'admin_menu', 'mps_care_add_support_page' );

/**
 * Transient key holding the last submission's result for the current user.
 *
 * @return string
 */
function mps_care_support_result_key() {
    return 'mps_care_support_result_' . get_current_user_id();
}

/**
 * Render the "Get support" page.
 */
function mps_care_render_support_page() {
    if ( ! current_user_can( 'manage_options' ) || ! mps_care_user_can_send_tickets() ) {
        return;
    }

    $result = get_transient( mps_care_support_result_key() );
    delete_transient( mps_care_support_result_key() );

    $values = array(
        'subject'  => '',
        'message'  => '',
        'priority' => 'medium',
    );

    if ( is_array( $result ) && ! empty( $result['values'] ) ) {
        $values = array_merge( $values, $result['values'] );
    }

    $priorities = array(
        'low'    => 'Low: whenever you get to it',
        'medium' => 'Medium: something is not right',
        'high'   => 'High: part of the site is broken',
        'urgent' => 'Urgent: the site is down or losing sales',
    );
    ?>
    <div class="wrap">
        <h1>Get support</h1>

        <?php if ( is_array( $result ) && 'sent' === $result['status'] ) : ?>
            <div class="notice notice-success">
                <p>
                    Ticket <strong><?php echo esc_html( $result['ticket_id'] ); ?></strong> is with My Project Solution.
                    We will reply by email.
                    <?php if ( ! empty( $result['public_url'] ) ) : ?>
                        You can also <a href="<?php echo esc_url( $result['public_url'] ); ?>" target="_blank" rel="noopener noreferrer">follow it in the client portal</a>.
                    <?php endif; ?>
                </p>
            </div>
        <?php elseif ( is_array( $result ) && 'error' === $result['status'] ) : ?>
            <div class="notice notice-error">
                <p><?php echo esc_html( $result['message'] ); ?></p>
                <?php if ( ! empty( $result['errors'] ) ) : ?>
                    <ul>
                        <?php foreach ( $result['errors'] as $error ) : ?>
                            <li><?php echo esc_html( $error ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <p>
            Tell My Project Solution what is wrong and we will take it from here. Your WordPress, PHP, theme,
            and plugin details are sent with the ticket, so you do not need to look them up.
        </p>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="mps_care_send_ticket" />
            <?php wp_nonce_field( 'mps_care_send_ticket' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="mps_care_subject">Subject</label></th>
                    <td>
                        <input type="text" id="mps_care_subject" name="subject" class="large-text" maxlength="255" required
                               value="<?php echo esc_attr( $values['subject'] ); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mps_care_priority">Priority</label></th>
                    <td>
                        <select id="mps_care_priority" name="priority">
                            <?php foreach ( $priorities as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $values['priority'], $value ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mps_care_message">What is happening?</label></th>
                    <td>
                        <textarea id="mps_care_message" name="message" class="large-text" rows="10" maxlength="20000" required><?php echo esc_textarea( $values['message'] ); ?></textarea>
                        <p class="description">Include the page address and what you expected to happen, if you can.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button( 'Send ticket' ); ?>
        </form>
    </div>
    <?php
}

/**
 * The site details sent with every ticket.
 *
 * @return array
 */
function mps_care_site_details() {
    $theme = wp_get_theme();

    return array(
        'url'          => home_url( '/' ),
        'wp_version'   => get_bloginfo( 'version' ),
        'php_version'  => PHP_VERSION,
        'theme'        => $theme->get( 'Name' ),
        'plugin_count' => count( (array) get_option( 'active_plugins', array() ) ),
    );
}

/**
 * Send a ticket to the portal.
 *
 * @param array $ticket   subject, message, priority.
 * @param array $reporter name, email.
 * @return array Result: status "sent" with ticket_id and public_url, or "error" with message and errors.
 */
function mps_care_send_ticket_to_portal( array $ticket, array $reporter ) {
    $response = wp_remote_post(
        mps_care_get_portal_url() . '/api/v1/tickets',
        array(
            'timeout' => 10,
            'headers' => array(
                'X-MPS-Site-Key' => mps_care_get_site_key(),
                'Content-Type'   => 'application/json',
                'Accept'         => 'application/json',
            ),
            'body'    => wp_json_encode(
                array(
                    'subject'  => $ticket['subject'],
                    'message'  => $ticket['message'],
                    'priority' => $ticket['priority'],
                    'site'     => mps_care_site_details(),
                    'reporter' => $reporter,
                )
            ),
        )
    );

    $fallback = 'The ticket could not be sent. Please try again in a few minutes, or email support@myprojectsolution.com.';

    if ( is_wp_error( $response ) ) {
        return array( 'status' => 'error', 'message' => $fallback );
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( 201 === $code && is_array( $body ) && ! empty( $body['ticket_id'] ) ) {
        return array(
            'status'     => 'sent',
            'ticket_id'  => (string) $body['ticket_id'],
            'public_url' => isset( $body['public_url'] ) ? (string) $body['public_url'] : '',
        );
    }

    if ( 403 === $code ) {
        return array(
            'status'  => 'error',
            'message' => 'The portal did not accept this site\'s key. Please email support@myprojectsolution.com and ask for a new site key.',
        );
    }

    if ( 429 === $code ) {
        return array(
            'status'  => 'error',
            'message' => 'This site has sent a lot of tickets in the last hour. Please wait a while, or email support@myprojectsolution.com.',
        );
    }

    if ( 422 === $code && is_array( $body ) && ! empty( $body['errors'] ) && is_array( $body['errors'] ) ) {
        $errors = array();

        foreach ( $body['errors'] as $messages ) {
            foreach ( (array) $messages as $message ) {
                $errors[] = (string) $message;
            }
        }

        return array( 'status' => 'error', 'message' => 'Please fix the following and send again.', 'errors' => $errors );
    }

    return array( 'status' => 'error', 'message' => $fallback );
}

/**
 * Handle the "Get support" form.
 */
function mps_care_handle_send_ticket() {
    if ( ! current_user_can( 'manage_options' ) || ! mps_care_user_can_send_tickets() ) {
        wp_die( 'You are not allowed to send support tickets.', 403 );
    }

    check_admin_referer( 'mps_care_send_ticket' );

    $priority = isset( $_POST['priority'] ) ? sanitize_key( wp_unslash( $_POST['priority'] ) ) : 'medium';

    $ticket = array(
        'subject'  => isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '',
        'message'  => isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '',
        'priority' => in_array( $priority, array( 'low', 'medium', 'high', 'urgent' ), true ) ? $priority : 'medium',
    );

    $user = wp_get_current_user();

    $result = mps_care_send_ticket_to_portal(
        $ticket,
        array(
            'name'  => trim( $user->display_name ) ? trim( $user->display_name ) : $user->user_login,
            'email' => $user->user_email,
        )
    );

    // Keep what the administrator typed if it did not go through, so they can send it again.
    if ( 'sent' !== $result['status'] ) {
        $result['values'] = $ticket;
    }

    set_transient( mps_care_support_result_key(), $result, 5 * MINUTE_IN_SECONDS );

    wp_safe_redirect( admin_url( 'index.php?page=mps-care-support' ) );
    exit;
}
add_action( 'admin_post_mps_care_send_ticket', 'mps_care_handle_send_ticket' );
