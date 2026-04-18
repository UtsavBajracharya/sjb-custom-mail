<?php
/**
 * Plugin Name: SJB Custom Mail
 * Description: Custom email override and settings for Simple Job Board.
 * Version: 1.0.0
 * Author: Utsav
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom setting
 */
add_action('admin_init', function () {
    register_setting('sjb_custom_mail_group', 'sjb_custom_hr_email', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default' => '',
    ]);

    register_setting('sjb_custom_mail_group', 'sjb_custom_from_name', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => get_bloginfo('name'),
    ]);

    register_setting('sjb_custom_mail_group', 'sjb_custom_from_email', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default' => get_option('admin_email'),
    ]);
});

/**
 * Add admin settings page
 */
add_action('admin_menu', function () {
    add_options_page(
        'SJB Custom Mail',
        'SJB Custom Mail',
        'manage_options',
        'sjb-custom-mail',
        'sjb_custom_mail_settings_page'
    );
});

function sjb_custom_mail_settings_page() {
    ?>
    <div class="wrap">
        <h1>SJB Custom Mail Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('sjb_custom_mail_group'); ?>
            <?php do_settings_sections('sjb_custom_mail_group'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sjb_custom_hr_email">HR Gmail</label></th>
                    <td>
                        <input type="email" name="sjb_custom_hr_email" id="sjb_custom_hr_email"
                               value="<?php echo esc_attr(get_option('sjb_custom_hr_email', '')); ?>"
                               class="regular-text" />
                        <p class="description">This will override the HR email used by Simple Job Board.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="sjb_custom_from_name">From Name</label></th>
                    <td>
                        <input type="text" name="sjb_custom_from_name" id="sjb_custom_from_name"
                               value="<?php echo esc_attr(get_option('sjb_custom_from_name', get_bloginfo('name'))); ?>"
                               class="regular-text" />
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="sjb_custom_from_email">From Email</label></th>
                    <td>
                        <input type="email" name="sjb_custom_from_email" id="sjb_custom_from_email"
                               value="<?php echo esc_attr(get_option('sjb_custom_from_email', get_option('admin_email'))); ?>"
                               class="regular-text" />
                        <p class="description">Use a domain-based email like careers@yourdomain.com when possible.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Override the HR recipient for Simple Job Board
 * SJB uses the sjb_hr_notification_to filter.
 */
add_filter('sjb_hr_notification_to', function ($to, $post_id) {
    $custom_hr_email = get_option('sjb_custom_hr_email', '');

    if (!empty($custom_hr_email) && is_email($custom_hr_email)) {
        return $custom_hr_email;
    }

    return $to;
}, 10, 2);

/**
 * Customize the HR email subject
 */
add_filter('sjb_hr_notification_sbj', function ($subject, $job_title, $post_id) {
    return sprintf('New job application received for %s', $job_title);
}, 10, 3);

/**
 * Set the From email and name globally for wp_mail
 * Only do this if you want to override the site default sender.
 */
add_filter('wp_mail_from', function ($from_email) {
    $custom_from_email = get_option('sjb_custom_from_email', '');

    if (!empty($custom_from_email) && is_email($custom_from_email)) {
        return $custom_from_email;
    }

    return $from_email;
});

add_filter('wp_mail_from_name', function ($from_name) {
    $custom_from_name = get_option('sjb_custom_from_name', '');

    if (!empty($custom_from_name)) {
        return $custom_from_name;
    }

    return $from_name;
});

/**
 * Customize the HR email body template
 * SJB exposes sjb_hr_email_template.
 */
add_filter('sjb_hr_email_template', function ($message, $post_id, $notification_receiver) {
    $job_title = get_the_title($post_id);

    $custom_message  = '<p>Hello HR,</p>';
    $custom_message .= '<p>A new application has been submitted on your website.</p>';
    $custom_message .= '<p><strong>Job Title:</strong> ' . esc_html($job_title) . '</p>';
    $custom_message .= '<p>Please log in to WordPress and review the applicant details and resume.</p>';
    $custom_message .= '<p>Regards,<br>' . esc_html(get_bloginfo('name')) . '</p>';

    return $custom_message;
}, 10, 3);

/**
 * Optional: log email failures for debugging
 */
add_action('wp_mail_failed', function ($error) {
    error_log('SJB Mail Failure: ' . $error->get_error_message());
    error_log(print_r($error->get_error_data(), true));
});