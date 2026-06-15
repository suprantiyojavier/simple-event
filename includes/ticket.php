<?php
/**
 * Ticket System
 * - Generate ticket URL with hash for security
 * - Display ticket page when QR code is scanned
 * - Generate QR code URL using API
 */

// Generate secure ticket hash
function se_generate_ticket_hash($submission_id) {
    return substr(md5('se_ticket_' . $submission_id . wp_salt('auth')), 0, 12);
}

// Generate ticket URL
function se_get_ticket_url($submission_id) {
    $hash = se_generate_ticket_hash($submission_id);
    return add_query_arg([
        'se_ticket' => $submission_id,
        'se_verify' => $hash,
    ], home_url('/'));
}

// Generate QR code image URL (using goqr.me API)
function se_get_qr_code_url($data, $size = 200) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data) . '&format=png&margin=5';
}

// Generate QR code HTML for email
function se_get_ticket_qr_html($submission_id) {
    $ticket_url = se_get_ticket_url($submission_id);
    $qr_url = se_get_qr_code_url($ticket_url, 200);

    return "<div style='text-align:center; margin:20px 0; padding:20px; background:#fff; border:2px dashed #ddd; border-radius:8px;'>
        <p style='font-weight:bold; margin-bottom:10px; color:#333;'>E-Ticket</p>
        <img src='" . esc_url($qr_url) . "' alt='QR Ticket' style='width:200px; height:200px;'>
        <p style='font-size:12px; color:#888; margin-top:10px;'>Scan this QR code to verify ticket</p>
    </div>";
}

// Handle ticket page display
function se_handle_ticket_page() {
    if (!isset($_GET['se_ticket']) || !isset($_GET['se_verify'])) {
        return;
    }

    $submission_id = intval($_GET['se_ticket']);
    $verify_hash = sanitize_text_field($_GET['se_verify']);

    // Verify hash
    $expected_hash = se_generate_ticket_hash($submission_id);
    if ($verify_hash !== $expected_hash) {
        wp_die('Invalid ticket.', 'Invalid Ticket', ['response' => 403]);
    }

    // Get submission data
    global $wpdb;
    $table_name = $wpdb->prefix . 'event_submissions';
    $submission = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $submission_id
    ));

    if (!$submission) {
        wp_die('Ticket data not found.', 'Ticket Not Found', ['response' => 404]);
    }

    // Get event data
    $event_id = $submission->event_id;
    $event_title = get_the_title($event_id);
    $event_image = get_the_post_thumbnail_url($event_id, 'medium');
    $start_date = get_post_meta($event_id, '_se_event_start_date', true);
    $start_time = get_post_meta($event_id, '_se_event_start_time', true);
    $end_time = get_post_meta($event_id, '_se_event_end_time', true);
    $location = get_post_meta($event_id, '_se_event_location', true);
    $display_date = !empty($start_date) ? date_i18n('l, d F Y', strtotime($start_date)) : '-';
    $form_type = isset($submission->form_type) ? $submission->form_type : 'registration';
    $type_label = $form_type === 'replay' ? 'Replay Viewer' : 'Event Attendee';

    // Get site logo
    $logo_html = '';
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo_image = wp_get_attachment_image_src($custom_logo_id, 'full');
        if ($logo_image) {
            $logo_html = '<img src="' . esc_url($logo_image[0]) . '" alt="' . esc_attr(get_bloginfo('name')) . '" style="max-width:150px; height:auto;">';
        }
    }

    // Render ticket page
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>E-Ticket - <?php echo esc_html($event_title); ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
            .ticket-card { background: #fff; border-radius: 16px; max-width: 420px; width: 100%; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
            .ticket-header { background: linear-gradient(135deg, #EA242A 0%, #c01e23 100%); color: white; padding: 24px; text-align: center; }
            .ticket-header.replay { background: linear-gradient(135deg, #2563EB 0%, #1d4ed8 100%); }
            .ticket-header .logo { margin-bottom: 12px; }
            .ticket-header .logo img { filter: brightness(0) invert(1); }
            .ticket-header h1 { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
            .ticket-header .badge { display: inline-block; background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-top: 8px; }
            .ticket-body { padding: 24px; }
            .ticket-event-img { width: 100%; height: 160px; object-fit: cover; border-radius: 8px; margin-bottom: 16px; }
            .ticket-divider { border: none; border-top: 2px dashed #e5e7eb; margin: 20px -24px; position: relative; }
            .ticket-divider::before, .ticket-divider::after { content: ''; position: absolute; top: -12px; width: 24px; height: 24px; background: #f0f2f5; border-radius: 50%; }
            .ticket-divider::before { left: -12px; }
            .ticket-divider::after { right: -12px; }
            .ticket-info { margin-bottom: 12px; }
            .ticket-info .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; font-weight: 600; margin-bottom: 2px; }
            .ticket-info .value { font-size: 15px; color: #1f2937; font-weight: 500; }
            .ticket-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
            .ticket-status { text-align: center; padding: 12px; background: #ecfdf5; border-radius: 8px; margin-top: 16px; }
            .ticket-status .icon { font-size: 24px; }
            .ticket-status p { color: #059669; font-weight: 600; font-size: 14px; }
            .ticket-footer { text-align: center; padding: 16px 24px; background: #f9fafb; font-size: 11px; color: #9ca3af; }
        </style>
    </head>
    <body>
        <div class="ticket-card">
            <div class="ticket-header <?php echo $form_type === 'replay' ? 'replay' : ''; ?>">
                <?php if ($logo_html): ?>
                    <div class="logo"><?php echo $logo_html; ?></div>
                <?php endif; ?>
                <h1><?php echo esc_html($event_title); ?></h1>
                <span class="badge"><?php echo esc_html($type_label); ?></span>
            </div>

            <div class="ticket-body">
                <?php if ($event_image): ?>
                    <img class="ticket-event-img" src="<?php echo esc_url($event_image); ?>" alt="<?php echo esc_attr($event_title); ?>">
                <?php endif; ?>

                <div class="ticket-grid">
                    <div class="ticket-info">
                        <div class="label">Date</div>
                        <div class="value"><?php echo esc_html($display_date); ?></div>
                    </div>
                    <div class="ticket-info">
                        <div class="label">Time</div>
                        <div class="value"><?php echo esc_html($start_time); ?> - <?php echo esc_html($end_time); ?></div>
                    </div>
                </div>

                <div class="ticket-info">
                    <div class="label">Location</div>
                    <div class="value"><?php echo esc_html($location); ?></div>
                </div>

                <hr class="ticket-divider">

                <?php
                $ticket_form_fields = se_get_event_form_fields($event_id);
                $ticket_default_keys = ['name', 'email', 'phone', 'company', 'job_title'];
                $ticket_custom = !empty($submission->custom_fields) ? json_decode($submission->custom_fields, true) : [];

                // Collect all field values
                $ticket_field_items = [];
                foreach ($ticket_form_fields as $tf) {
                    $tk = $tf['key'];
                    $val = '';
                    if (in_array($tk, $ticket_default_keys)) {
                        $val = isset($submission->$tk) ? $submission->$tk : '';
                    } else {
                        $val = isset($ticket_custom[$tk]) ? $ticket_custom[$tk] : '';
                    }
                    if (!empty($val)) {
                        $ticket_field_items[] = ['label' => $tf['label'], 'value' => $val, 'key' => $tk];
                    }
                }

                // Render in grid pairs
                $chunks = array_chunk($ticket_field_items, 2);
                foreach ($chunks as $chunk):
                ?>
                <div class="ticket-grid">
                    <?php foreach ($chunk as $item): ?>
                    <div class="ticket-info">
                        <div class="label"><?php echo esc_html($item['label']); ?></div>
                        <div class="value" <?php echo $item['key'] === 'email' ? 'style="font-size:13px; word-break:break-all;"' : ''; ?>><?php echo esc_html($item['value']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <div class="ticket-status">
                    <div class="icon">&#10003;</div>
                    <p>Registered</p>
                </div>
            </div>

            <div class="ticket-footer">
                Ticket #<?php echo esc_html($submission_id); ?> &bull; Registered <?php echo esc_html(date_i18n('d M Y, H:i', strtotime($submission->created_at))); ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
add_action('template_redirect', 'se_handle_ticket_page');
