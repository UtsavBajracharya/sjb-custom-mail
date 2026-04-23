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
                    <th scope="row"><label for="sjb_custom_hr_email">HR Email</label></th>
                    <td>
                        <input type="email" name="sjb_custom_hr_email" id="sjb_custom_hr_email"
                               value="<?php echo esc_attr(get_option('sjb_custom_hr_email', '')); ?>"
                               class="regular-text" />
                        <p class="description">Email address where job application notifications will be sent.</p>
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
 * Helper function to generate HTML email header
 */
function sjb_get_email_header() {
    $site_name = get_bloginfo('name');
    $site_url = get_home_url();

    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style type="text/css">
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .email-container { max-width: 600px; margin: 0 auto; background-color: #f9f9f9; }
            .email-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .email-header h1 { margin: 0; font-size: 24px; }
            .email-body { background-color: white; padding: 30px; }
            .email-body p { margin: 15px 0; }
            .email-body h2 { color: #667eea; margin-top: 20px; margin-bottom: 10px; }
            .job-details { background-color: #f5f5f5; padding: 15px; border-left: 4px solid #667eea; margin: 20px 0; }
            .job-details strong { color: #667eea; }
            .cta-button { display: inline-block; background-color: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 15px 0; }
            .cta-button:hover { background-color: #764ba2; }
            .email-footer { background-color: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; }
            .email-footer a { color: #667eea; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>' . esc_html($site_name) . '</h1>
                <p>Careers Portal</p>
            </div>
            <div class="email-body">
    ';
}

/**
 * Helper function to generate HTML email footer
 */
function sjb_get_email_footer() {
    $site_name = get_bloginfo('name');
    $site_url = get_home_url();

    return '
            </div>
            <div class="email-footer">
                <p>&copy; ' . date('Y') . ' ' . esc_html($site_name) . '. All rights reserved.</p>
                <p><a href="' . esc_url($site_url) . '">Visit our website</a></p>
            </div>
        </div>
    </body>
    </html>
    ';
}

/**
 * Set emails to HTML content type
 */
add_filter('wp_mail_content_type', function () {
    return 'text/html';
});

/**
 * Customize the HR email body template
 * SJB exposes sjb_hr_email_template.
 */
add_filter('sjb_hr_email_template', function ($message, $post_id, $notification_receiver) {
    $job_title = get_the_title($post_id);
    $site_url = get_home_url();

    // Get applicant name from post meta
    $applicant_name = get_post_meta($post_id, 'applicant_name', true);
    if (empty($applicant_name)) {
        $applicant_name = 'N/A';
    }

    // Get the resume attachment
    $resume_url = '';
    $resume_path = '';
    $resume_file_name = '';
    $attachments = get_posts(array(
        'post_type' => 'attachment',
        'post_parent' => $post_id,
        'numberposts' => 1,
        'post_status' => 'inherit'
    ));
    if (!empty($attachments)) {
        $resume_url = wp_get_attachment_url($attachments[0]->ID);
        $resume_path = get_attached_file($attachments[0]->ID);
        $resume_file_name = basename($resume_path);
    }

    // Store resume path for email attachment hook
    update_option('sjb_current_resume_path', $resume_path);

    $custom_message  = sjb_get_email_header();
    $custom_message .= '<h2>New Job Application Received</h2>';
    $custom_message .= '<p>A new application has been submitted on your website.</p>';
    $custom_message .= '<div class="job-details">';
    $custom_message .= '<p><strong>Applicant Name:</strong> ' . esc_html($applicant_name) . '</p>';
    $custom_message .= '<p><strong>Job Title:</strong> ' . esc_html($job_title) . '</p>';
    $custom_message .= '<p><strong>Application Date:</strong> ' . date('F j, Y \a\t g:i A') . '</p>';
    if (!empty($resume_url)) {
        $custom_message .= '<p><strong>Resume:</strong> <a href="' . esc_url($resume_url) . '" target="_blank" style="color: #667eea; font-weight: bold;">📥 Download Resume</a></p>';
        $custom_message .= '<p style="font-size: 12px; color: #666; margin-top: 5px;"><em>Resume is also attached to this email.</em></p>';
    } else {
        $custom_message .= '<p><strong>Resume:</strong> <em>No resume attached</em></p>';
    }
    $custom_message .= '</div>';
    $custom_message .= '<h2>Next Steps</h2>';
    $custom_message .= '<p>Log in to WordPress to review the applicant\'s complete details:</p>';
    $custom_message .= '<p><a href="' . esc_url(admin_url('post.php?post=' . intval($post_id) . '&action=edit')) . '" class="cta-button">Review Full Application</a></p>';
    $custom_message .= '<p>You can view all applications and manage the hiring process from your WordPress dashboard.</p>';
    $custom_message .= sjb_get_email_footer();

    return $custom_message;
}, 10, 3);

/**
 * Customize the applicant confirmation email
 * SJB exposes sjb_app_email_template for applicant emails.
 */
add_filter('sjb_app_email_template', function ($message, $post_id, $app_id) {
    $job_title = get_the_title($post_id);
    $site_url = get_home_url();
    $site_name = get_bloginfo('name');

    $custom_message  = sjb_get_email_header();
    $custom_message .= '<h2>Application Received!</h2>';
    $custom_message .= '<p>Thank you for your interest in the <strong>' . esc_html($job_title) . '</strong> position at ' . esc_html($site_name) . '.</p>';
    $custom_message .= '<div class="job-details">';
    $custom_message .= '<p>We have successfully received your application. Our recruitment team will review your qualifications and get back to you soon.</p>';
    $custom_message .= '</div>';
    $custom_message .= '<h2>What Happens Next?</h2>';
    $custom_message .= '<ul style="margin: 15px 0; padding-left: 20px;">';
    $custom_message .= '<li>Our team will review your application</li>';
    $custom_message .= '<li>Selected candidates will be contacted for an interview</li>';
    $custom_message .= '<li>We will keep you updated throughout the process</li>';
    $custom_message .= '</ul>';
    $custom_message .= '<h2>Explore More Opportunities</h2>';
    $custom_message .= '<p>In the meantime, you can explore other job openings on our careers portal:</p>';
    $custom_message .= '<p><a href="' . esc_url($site_url) . '/careers" class="cta-button">View More Jobs</a></p>';
    $custom_message .= '<h2>Questions?</h2>';
    $hr_email = get_option('sjb_custom_hr_email', '');
    if (!empty($hr_email)) {
        $custom_message .= '<p>If you have any questions about your application, feel free to reach out to our HR team at <a href="mailto:' . esc_attr($hr_email) . '">' . esc_html($hr_email) . '</a>.</p>';
    }
    $custom_message .= sjb_get_email_footer();

    return $custom_message;
}, 10, 3);

/**
 * Optional: log email failures for debugging
 */
add_action('wp_mail_failed', function ($error) {
    error_log('SJB Mail Failure: ' . $error->get_error_message());
    error_log(print_r($error->get_error_data(), true));
});