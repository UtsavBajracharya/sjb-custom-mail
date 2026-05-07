<?php
/**
 * Plugin Name: SJB Custom Mail
 * Description: Custom HR and applicant email templates for Simple Job Board.
 * Version: 1.1.1
 * Author: Utsav
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom HR email setting
 */
add_action('admin_init', function () {
    register_setting('sjb_custom_mail_group', 'sjb_custom_hr_email', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default'           => '',
    ]);
});

/**
 * Add settings page
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
                    <th scope="row">
                        <label for="sjb_custom_hr_email">HR Email</label>
                    </th>
                    <td>
                        <input type="email"
                               name="sjb_custom_hr_email"
                               id="sjb_custom_hr_email"
                               value="<?php echo esc_attr(get_option('sjb_custom_hr_email', '')); ?>"
                               class="regular-text" />
                        <p class="description">
                            Job application notifications will be sent to this email.
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Force all SJB emails to use HTML format
 */
add_filter('wp_mail_content_type', function () {
    return 'text/html';
});

/**
 * Override HR recipient
 */
add_filter('sjb_hr_notification_to', function ($to, $post_id) {
    $custom_hr_email = get_option('sjb_custom_hr_email', '');

    if (!empty($custom_hr_email) && is_email($custom_hr_email)) {
        return $custom_hr_email;
    }

    return $to;
}, 999, 2);

/**
 * HR email subject
 */
add_filter('sjb_hr_notification_sbj', function ($subject, $job_title, $post_id) {
    return sprintf('New Job Application Received for %s', html_entity_decode($job_title));
}, 999, 3);

/**
 * Applicant email subject
 * Correct hook for Simple Job Board 2.14.2
 */
add_filter('sjb_applicant_notification_sbj', function ($subject, $job_title, $post_id) {
    return sprintf('Application Received for %s', html_entity_decode($job_title));
}, 999, 3);

/**
 * Attach applicant resume only to HR notification email.
 */
add_filter('sjb_hr_notification_attachment', function ($attachment, $post_id) {
    $resume = sjb_custom_get_resume_data($post_id);

    if (!empty($resume['path']) && file_exists($resume['path'])) {
        return [$resume['path']];
    }

    return $attachment;
}, 999, 2);

/**
 * Get applicant resume URL and file path from Simple Job Board application meta.
 */
function sjb_custom_get_resume_data($post_id) {
    $resume_url  = get_post_meta($post_id, 'resume', true);
    $resume_path = get_post_meta($post_id, 'resume_path', true);

    if (!empty($resume_url) && $resume_url !== 'Resume[deleted]' && $resume_url !== '/') {
        return [
            'url'  => esc_url($resume_url),
            'path' => $resume_path,
        ];
    }

    return [
        'url'  => '',
        'path' => '',
    ];
}

/**
 * Email header
 */
function sjb_get_email_header() {
    $site_name = get_bloginfo('name');

    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style type="text/css">
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333333;
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }

            .email-container {
                max-width: 620px;
                margin: 0 auto;
                background-color: #ffffff;
                border: 1px solid #e5e5e5;
            }

            .email-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #ffffff;
                padding: 30px;
                text-align: center;
            }

            .email-header h1 {
                margin: 0;
                font-size: 24px;
                color: #ffffff;
            }

            .email-header p {
                margin: 8px 0 0;
                color: #ffffff;
            }

            .email-body {
                background-color: #ffffff;
                padding: 30px;
            }

            .email-body h2 {
                color: #667eea;
                margin-top: 20px;
                margin-bottom: 10px;
                font-size: 20px;
            }

            .email-body p {
                margin: 14px 0;
                font-size: 15px;
            }

            .job-details {
                background-color: #f8f9fa;
                padding: 16px;
                border-left: 4px solid #667eea;
                margin: 20px 0;
            }

            .job-details strong {
                color: #333333;
            }

            .cta-button {
                display: inline-block;
                background-color: #667eea;
                color: #ffffff !important;
                padding: 12px 24px;
                text-decoration: none;
                border-radius: 5px;
                margin: 15px 0;
                font-weight: bold;
            }

            .email-footer {
                background-color: #f9f9f9;
                padding: 20px;
                text-align: center;
                font-size: 12px;
                color: #666666;
                border-top: 1px solid #dddddd;
            }

            .email-footer a {
                color: #667eea;
                text-decoration: none;
            }
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
 * Email footer
 */
function sjb_get_email_footer() {
    $site_name = get_bloginfo('name');
    $site_url  = get_home_url();

    return '
            </div>
            <div class="email-footer">
                <p>&copy; ' . esc_html(date('Y')) . ' ' . esc_html($site_name) . '. All rights reserved.</p>
                <p><a href="' . esc_url($site_url) . '">Visit our website</a></p>
            </div>
        </div>
    </body>
    </html>
    ';
}

/**
 * HR email template
 */
add_filter('sjb_hr_email_template', function ($message, $post_id, $notification_receiver) {
    $job_title = get_the_title($post_id);

    $applicant_name  = get_post_meta($post_id, 'jobapp_name', true);
    $applicant_email = get_post_meta($post_id, 'jobapp_email', true);
    $applicant_phone = get_post_meta($post_id, 'jobapp_phone', true);

    if (empty($applicant_name)) {
        $applicant_name = get_post_meta($post_id, 'applicant_name', true);
    }

    if (empty($applicant_email)) {
        $applicant_email = get_post_meta($post_id, 'email', true);
    }

    if (empty($applicant_phone)) {
        $applicant_phone = get_post_meta($post_id, 'phone', true);
    }

    $applicant_name  = !empty($applicant_name) ? $applicant_name : 'N/A';
    $applicant_email = !empty($applicant_email) ? $applicant_email : 'N/A';
    $applicant_phone = !empty($applicant_phone) ? $applicant_phone : 'N/A';

    $resume = sjb_custom_get_resume_data($post_id);
    $resume_url = $resume['url'];

    $custom_message  = sjb_get_email_header();
    $custom_message .= '<h2>New Job Application Received</h2>';
    $custom_message .= '<p>A new candidate has submitted an application through the careers page.</p>';

    $custom_message .= '<div class="job-details">';
    $custom_message .= '<p><strong>Applicant Name:</strong> ' . esc_html($applicant_name) . '</p>';
    $custom_message .= '<p><strong>Email:</strong> ' . esc_html($applicant_email) . '</p>';
    $custom_message .= '<p><strong>Phone:</strong> ' . esc_html($applicant_phone) . '</p>';
    $custom_message .= '<p><strong>Job Title:</strong> ' . esc_html($job_title) . '</p>';
    $custom_message .= '<p><strong>Submitted On:</strong> ' . esc_html(current_time('F j, Y \a\t g:i A')) . '</p>';

    if (!empty($resume['path'])) {
    $custom_message .= '<p><strong>Resume:</strong> Attached with this email.</p>';
    } else {
        $custom_message .= '<p><strong>Resume:</strong> Not available</p>';
    }

    $custom_message .= '</div>';

    $custom_message .= '<p><a href="' . esc_url(admin_url('post.php?post=' . intval($post_id) . '&action=edit')) . '" class="cta-button">Review Application</a></p>';

    $custom_message .= '<p>Please log in to the WordPress dashboard to review the complete application details.</p>';

    $custom_message .= sjb_get_email_footer();

    return $custom_message;
}, 999, 3);

/**
 * Applicant confirmation email template
 * Correct hook for Simple Job Board 2.14.2:
 * sjb_applicant_email_template
 */
add_filter('sjb_applicant_email_template', function ($message, $post_id, $notification_receiver) {
    $job_title = get_the_title($post_id);
    $site_name = get_bloginfo('name');
    $site_url  = get_home_url();
    $hr_email  = get_option('sjb_custom_hr_email', '');

    $applicant_name = get_post_meta($post_id, 'jobapp_name', true);

    if (empty($applicant_name)) {
        $applicant_name = get_post_meta($post_id, 'applicant_name', true);
    }

    if (empty($applicant_name)) {
        $applicant_name = 'Applicant';
    }

    $custom_message  = sjb_get_email_header();

    $custom_message .= '<h2>Application Submitted Successfully</h2>';

    $custom_message .= '<p>Dear ' . esc_html($applicant_name) . ',</p>';

    $custom_message .= '<p>Thank you for applying for the <strong>' . esc_html($job_title) . '</strong> position at <strong>' . esc_html($site_name) . '</strong>.</p>';

    $custom_message .= '<div class="job-details">';
    $custom_message .= '<p>We have successfully received your application.</p>';
    $custom_message .= '<p>Our hiring team will review your profile, experience, and submitted documents. If your qualifications match our requirements, we will contact you for the next step in the recruitment process.</p>';
    $custom_message .= '</div>';

    $custom_message .= '<h2>Application Summary</h2>';
    $custom_message .= '<p><strong>Position Applied For:</strong> ' . esc_html($job_title) . '</p>';
    $custom_message .= '<p><strong>Submitted On:</strong> ' . esc_html(current_time('F j, Y \a\t g:i A')) . '</p>';

    $custom_message .= '<h2>Next Steps</h2>';
    $custom_message .= '<ul style="margin: 15px 0; padding-left: 20px;">';
    $custom_message .= '<li>Your application will be reviewed by our hiring team.</li>';
    $custom_message .= '<li>Shortlisted candidates will be contacted for further discussion or interview.</li>';
    $custom_message .= '<li>No further action is required from your side at this moment.</li>';
    $custom_message .= '</ul>';

    if (!empty($hr_email) && is_email($hr_email)) {
        $custom_message .= '<h2>Questions?</h2>';
        $custom_message .= '<p>If you have any questions about your application, please contact our HR team at <a href="mailto:' . esc_attr($hr_email) . '">' . esc_html($hr_email) . '</a>.</p>';
    }

    $custom_message .= '<p><a href="' . esc_url($site_url) . '" class="cta-button">Visit Our Website</a></p>';

    $custom_message .= '<p>Thank you again for your interest in joining ' . esc_html($site_name) . '.</p>';

    $custom_message .= '<p>Best regards,<br><strong>' . esc_html($site_name) . ' Hiring Team</strong></p>';

    $custom_message .= sjb_get_email_footer();

    return $custom_message;
}, 999, 3);

/**
 * Log email failures
 */
add_action('wp_mail_failed', function ($error) {
    error_log('SJB Mail Failure: ' . $error->get_error_message());
    error_log(print_r($error->get_error_data(), true));
});