<?php
/**
 * Replay Form Shortcode
 * Display form "Watch the Replay" for events that have ended.
 * After submit, display embedded YouTube video.
 */

function se_extract_youtube_id($url) {
    $video_id = '';

    // Format: youtube.com/watch?v=VIDEO_ID
    if (preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $video_id = $matches[1];
    }
    // Format: youtu.be/VIDEO_ID
    elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $video_id = $matches[1];
    }
    // Format: youtube.com/embed/VIDEO_ID
    elseif (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $video_id = $matches[1];
    }

    return $video_id;
}

function se_render_youtube_embed($url) {
    $video_id = se_extract_youtube_id($url);
    if (empty($video_id)) return '';

    return '<div class="se-video-container" style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; max-width:100%; border-radius:8px; margin-top:1rem;">
        <iframe src="https://www.youtube.com/embed/' . esc_attr($video_id) . '"
            style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen></iframe>
    </div>';
}

function se_event_replay_form($atts) {
    ob_start();
    global $post, $wpdb;

    $event_id = $post->ID;
    $table_name = $wpdb->prefix . 'event_submissions';
    $replay_url = get_post_meta($event_id, '_se_event_replay_url', true);
    $form_fields = se_get_event_form_fields($event_id);
    $default_keys = ['name', 'email', 'phone', 'company', 'job_title'];

    // Check if tel field exists and enqueue intl-tel-input
    $has_tel = false;
    foreach ($form_fields as $ff) {
        if (($ff['type'] ?? 'text') === 'tel') { $has_tel = true; break; }
    }
    if ($has_tel) { se_enqueue_intl_tel_input(); }

    if (empty($replay_url)) {
        return ob_get_clean();
    }

    $show_video = false;
    $form_submitted = false;

    // Check if form was just submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['se_replay_register']) && wp_verify_nonce($_POST['_se_replay_nonce'] ?? '', 'se_replay_' . $event_id)) {
        $db_data = [
            'event_id'   => $event_id,
            'form_type'  => 'replay',
            'created_at' => current_time('mysql'),
        ];
        $custom_data = [];

        foreach ($form_fields as $field) {
            $key = $field['key'];
            $post_key = 'se_' . $key;
            $field_type = $field['type'] ?? 'text';

            if ($field_type === 'checkbox' && isset($_POST[$post_key]) && is_array($_POST[$post_key])) {
                $value = implode(', ', array_map('sanitize_text_field', $_POST[$post_key]));
            } else {
                $value = isset($_POST[$post_key]) ? sanitize_text_field($_POST[$post_key]) : '';
            }

            if (in_array($key, $default_keys)) {
                $db_data[$key] = ($key === 'email') ? sanitize_email($value) : sanitize_text_field($value);
            } else {
                $custom_data[$key] = $value;
            }
        }

        if (!empty($custom_data)) {
            $db_data['custom_fields'] = wp_json_encode($custom_data);
        }

        $email = $db_data['email'] ?? '';
        $name = $db_data['name'] ?? '';

        // Check blocked email domains
        $blocked_domains_raw = get_post_meta($event_id, '_se_event_blocked_email_domains', true);
        $email_domain = strtolower(substr(strrchr($email, '@'), 1));
        $is_domain_blocked = false;
        if (!empty($blocked_domains_raw) && !empty($email_domain)) {
            $blocked_list = array_filter(array_map('trim', array_map('strtolower', explode(',', $blocked_domains_raw))));
            $is_domain_blocked = in_array($email_domain, $blocked_list, true);
        }

        // Check if email has already submitted replay for this event
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE event_id = %d AND email = %s AND form_type = %s",
            $event_id,
            $email,
            'replay'
        ));

        if ($is_domain_blocked) {
            $replay_error = 'Email domain "' . esc_html($email_domain) . '" is not allowed. Please use a valid email Business.';
        } elseif ($exists > 0) {
            // Already registered, show video directly
            $show_video = true;
            $form_submitted = true;
        } else {
            $wpdb->insert($table_name, $db_data);
            $submission_id = $wpdb->insert_id;

            // Send email with replay video link + QR ticket
            $event_title = get_the_title($event_id);
            $event_url = get_permalink($event_id);
            $ticket_qr_html = se_get_ticket_qr_html($submission_id);
            $feedback_url = get_post_meta($event_id, '_se_event_feedback_form_url', true);

            $feedback_html = '';
            if (!empty($feedback_url)) {
                $feedback_html = "
    <div style='background: #FFF8E1; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #FFC107;'>
        <p style='margin: 0 0 10px; font-weight: bold;'>We Value Your Feedback!</p>
        <p style='margin: 0 0 10px;'>Please take a moment to share your feedback about this event:</p>
        <p style='margin: 0;'><a href='" . esc_url($feedback_url) . "' style='display:inline-block; background:#FFC107; color:#333; padding:10px 20px; text-decoration:none; border-radius:4px; font-weight:bold;'>Give Feedback</a></p>
    </div>";
            }

            $subject = 'Replay Video - ' . $event_title;
            $logo_html = se_get_email_logo_html();
            $message = "
<html><body style='font-family: Arial, sans-serif; color: #333;'>
<div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
    {$logo_html}
    <h2 style='color: #2563EB;'>Replay Video Event</h2>
    <p>Hello <strong>{$name}</strong>,</p>
    <p>Thank you for registering to watch the event replay:</p>
    <div style='background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 15px 0;'>
        <h3 style='margin-top: 0;'>{$event_title}</h3>
    </div>
    {$ticket_qr_html}
    {$feedback_html}
    <p>Please watch the replay video via the link below:</p>
    <p><a href='" . esc_url($replay_url) . "' style='display:inline-block; background:#2563EB; color:white; padding:10px 20px; text-decoration:none; border-radius:4px;'>Watch Replay Video</a></p>
    <p style='margin-top:10px;'>Or visit the event page to watch directly:</p>
    <p><a href='{$event_url}'>{$event_url}</a></p>
    <hr style='margin: 20px 0; border: none; border-top: 1px solid #ddd;'>
    <p style='font-size: 12px; color: #888;'>This email was sent automatically, please do not reply to this email.</p>
</div>
</body></html>";

            $headers = ['Content-Type: text/html; charset=UTF-8'];
            wp_mail($email, $subject, $message, $headers);

            $show_video = true;
            $form_submitted = true;
        }
    }

    if ($show_video) {
        echo '<div style="text-align:center;">';
        echo '<p style="color:green; font-weight:bold; margin-bottom:1rem;">Thank you! Please watch the event replay below.</p>';
        echo se_render_youtube_embed($replay_url);
        echo '</div>';
    } else {
        ?>
        <div style="width: 100%;">
            <?php if (!empty($replay_error)): ?>
                <p style="color:red;"><?php echo esc_html($replay_error); ?></p>
            <?php endif; ?>
            <form method="post">
                <?php wp_nonce_field('se_replay_' . $event_id, '_se_replay_nonce'); ?>
                <?php foreach ($form_fields as $field): ?>
                    <?php se_render_form_field($field, 'se_replay'); ?>
                <?php endforeach; ?>
                <p><button type="submit" name="se_replay_register" style="width:100%; padding: 12px 20px; background-color: #2563EB; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 16px;">Watch the Replay</button></p>
            </form>
        </div>
        <?php
        if ($has_tel) { se_render_intl_tel_init(); }
    }

    return ob_get_clean();
}

add_shortcode('event_replay_form', 'se_event_replay_form');
