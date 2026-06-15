<?php
// Post Type: Event
function se_register_event_post_type() {
    register_post_type('event', [
        'labels' => [
            'name' => 'Events',
            'singular_name' => 'Event',
        ],
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'event'],
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('event_category', 'event', [
        'labels' => [
            'name'              => 'Event Categories',
            'singular_name'     => 'Event Category',
            'search_items'      => 'Search Categories',
            'all_items'         => 'All Categories',
            'parent_item'       => 'Parent Category',
            'parent_item_colon' => 'Parent Category:',
            'edit_item'         => 'Edit Category',
            'update_item'       => 'Update Category',
            'add_new_item'      => 'Add New Category',
            'new_item_name'     => 'New Category Name',
            'menu_name'         => 'Categories',
        ],
        'hierarchical' => true,
        'public' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'rewrite' => ['slug' => 'event-category'],
    ]);
}
add_action('init', 'se_register_event_post_type');

// Add meta box to display registrants
function se_add_submission_meta_box() {
    add_meta_box(
        'se_event_submissions',
        'Registrant List',
        'se_render_submission_meta_box',
        'event',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'se_add_submission_meta_box');

// Render meta box content
function se_render_submission_meta_box($post) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'event_submissions';
    $event_id = $post->ID;

    // Notification if registrant is deleted
    if (isset($_GET['message']) && $_GET['message'] === 'deleted') {
        echo '<div class="notice notice-success is-dismissible"><p>Registrant successfully deleted.</p></div>';
    }

    // Pagination setup
    $per_page = 15;
    $paged = isset($_GET['paged_submission']) ? max(1, intval($_GET['paged_submission'])) : 1;
    $offset = ($paged - 1) * $per_page;

    // Count total
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE event_id = %d",
        $event_id
    ));
    $total_pages = ceil($total / $per_page);

    // Export button
    echo '<p>';
    echo '<a href="' . esc_url(add_query_arg(['se_export' => 'csv', 'event_id' => $event_id])) . '" class="button button-primary" style="margin-right:10px;">Export CSV</a>';

    // Get data with pagination
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE event_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $event_id, $per_page, $offset
    ));

    $form_fields = se_get_event_form_fields($event_id);
    $default_keys = ['name', 'email', 'phone', 'company', 'job_title'];

    if ($results) {
        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        foreach ($form_fields as $field) {
            echo '<th>' . esc_html($field['label']) . '</th>';
        }
        echo '<th>Type</th><th>Registration Time</th><th>QR Ticket</th><th>Action</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        foreach ($results as $row) {
            echo '<tr>';
            $custom = !empty($row->custom_fields) ? json_decode($row->custom_fields, true) : [];
            foreach ($form_fields as $field) {
                $key = $field['key'];
                if (in_array($key, $default_keys)) {
                    echo '<td>' . esc_html(isset($row->$key) ? $row->$key : '-') . '</td>';
                } else {
                    echo '<td>' . esc_html(isset($custom[$key]) ? $custom[$key] : '-') . '</td>';
                }
            }
            $form_type = isset($row->form_type) ? $row->form_type : 'registration';
            $type_label = $form_type === 'replay' ? 'Replay' : 'Registration';
            $type_color = $form_type === 'replay' ? '#2563EB' : '#16a34a';
            echo '<td><span style="background-color:' . $type_color . '; color:white; padding:2px 8px; border-radius:10px; font-size:12px;">' . $type_label . '</span></td>';
            echo '<td>' . esc_html(date('d M Y H:i', strtotime($row->created_at))) . '</td>';
            $ticket_url = se_get_ticket_url($row->id);
            $qr_url = se_get_qr_code_url($ticket_url, 100);
            echo '<td><a href="' . esc_url($ticket_url) . '" target="_blank"><img src="' . esc_url($qr_url) . '" alt="QR Ticket" style="width:80px; height:80px; border-radius:4px;" /></a></td>';

            $delete_url = wp_nonce_url(
                admin_url('admin-post.php?action=se_delete_submission&submission_id=' . $row->id . '&event_id=' . $event_id),
                'se_delete_submission_' . $row->id
            );

            echo '<td><a href="' . esc_url($delete_url) . '" class="button button-small" onclick="return confirm(\'Are you sure you want to delete this registrant?\')">Delete</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        // Pagination controls
        echo '<div style="margin-top:15px;">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $url = add_query_arg([
                'paged_submission' => $i,
                'post' => $post->ID,
                'action' => 'edit'
            ]);
            if ($i === $paged) {
                echo "<strong style='margin-right:5px;'>$i</strong>";
            } else {
                echo "<a href='" . esc_url($url) . "' style='margin-right:5px;'>$i</a>";
            }
        }
        echo '</div>';
    } else {
        echo '<p>No registrants yet.</p>';
    }
}

// Handler to delete submission
add_action('admin_post_se_delete_submission', 'se_handle_delete_submission');

function se_handle_delete_submission() {
    if (!current_user_can('edit_posts') || !isset($_GET['submission_id']) || !isset($_GET['event_id'])) {
        wp_die('Unauthorized access');
    }

    $submission_id = intval($_GET['submission_id']);
    $event_id = intval($_GET['event_id']);

    if (!wp_verify_nonce($_GET['_wpnonce'], 'se_delete_submission_' . $submission_id)) {
        wp_die('Invalid nonce');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'event_submissions';

    $wpdb->delete($table_name, ['id' => $submission_id]);

    wp_redirect(admin_url('post.php?post=' . $event_id . '&action=edit&message=deleted'));
    exit;
}

// Handler CSV Export
add_action('admin_init', 'se_handle_csv_export');

function se_handle_csv_export() {
    if (!isset($_GET['se_export']) || $_GET['se_export'] !== 'csv' || !isset($_GET['event_id'])) {
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized access');
    }

    $event_id = intval($_GET['event_id']);
    $event_title = sanitize_file_name(get_the_title($event_id));

    global $wpdb;
    $table_name = $wpdb->prefix . 'event_submissions';
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE event_id = %d ORDER BY created_at DESC",
        $event_id
    ));

    $form_fields = se_get_event_form_fields($event_id);
    $default_keys = ['name', 'email', 'phone', 'company', 'job_title'];

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="registrants-' . $event_title . '-' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Header row
    $headers = [];
    foreach ($form_fields as $field) {
        $headers[] = $field['label'];
    }
    $headers[] = 'Type';
    $headers[] = 'Registration Time';
    fputcsv($output, $headers);

    // Data rows
    foreach ($results as $row) {
        $csv_row = [];
        $custom = !empty($row->custom_fields) ? json_decode($row->custom_fields, true) : [];

        foreach ($form_fields as $field) {
            $key = $field['key'];
            if (in_array($key, $default_keys)) {
                $csv_row[] = isset($row->$key) ? $row->$key : '';
            } else {
                $csv_row[] = isset($custom[$key]) ? $custom[$key] : '';
            }
        }

        $form_type = isset($row->form_type) ? $row->form_type : 'registration';
        $csv_row[] = $form_type === 'replay' ? 'Replay' : 'Registration';
        $csv_row[] = date('d M Y H:i', strtotime($row->created_at));

        fputcsv($output, $csv_row);
    }

    fclose($output);
    exit;
}
