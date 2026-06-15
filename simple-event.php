<?php
/**
 * Plugin Name: Simple Event
 * Plugin URI: https://github.com/Gioidstar/simple-event
 * Description: Plugin to create events and registration forms with submission system.
 * Version: 2.1.7
 * Author: Gio fandi
 * Author URI: https://github.com/Gioidstar
 */


// Load all includes
require_once plugin_dir_path(__FILE__) . 'includes/class-github-updater.php';
require_once plugin_dir_path(__FILE__) . 'includes/post-types.php';
require_once plugin_dir_path(__FILE__) . 'includes/meta-boxes.php';
require_once plugin_dir_path(__FILE__) . 'includes/registration-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-event-list.php'; // display submission data in admin
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-event-compact.php';
require_once plugin_dir_path(__FILE__) . 'includes/replay-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/ticket.php';

// Elementor Integration
function se_register_elementor_widgets($widgets_manager) {
    require_once plugin_dir_path(__FILE__) . 'includes/widgets/elementor-event-grid.php';
    require_once plugin_dir_path(__FILE__) . 'includes/widgets/elementor-event-compact.php';
    $widgets_manager->register(new SE_Elementor_Event_Grid_Widget());
    $widgets_manager->register(new SE_Elementor_Event_Compact_Widget());
}
add_action('elementor/widgets/register', 'se_register_elementor_widgets');

// Auto-migrate DB columns (runs once per site, then skips permanently)
function se_maybe_migrate_db() {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $db_version = get_option('se_db_version', '0');
    if (version_compare($db_version, '2.1.0', '>=')) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'event_submissions';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        return;
    }

    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name", ARRAY_A);
    $existing_columns = array_column($columns, 'Field');

    $alter_queries = [];
    if (!in_array('form_type', $existing_columns)) {
        $alter_queries[] = "ADD COLUMN form_type VARCHAR(20) NOT NULL DEFAULT 'registration'";
    }
    if (!in_array('custom_fields', $existing_columns)) {
        $alter_queries[] = "ADD COLUMN custom_fields TEXT DEFAULT NULL";
    }

    if (!empty($alter_queries)) {
        $wpdb->query("ALTER TABLE $table_name " . implode(", ", $alter_queries));
    }

    update_option('se_db_version', '2.1.0', false);
}
add_action('init', 'se_maybe_migrate_db');

// Enqueue frontend assets only on single event pages
function se_enqueue_frontend_assets() {
    if (!is_singular('event')) return;
    wp_enqueue_style('se-single-event', plugin_dir_url(__FILE__) . 'assets/css/single-event.css', [], time());
}
add_action('wp_enqueue_scripts', 'se_enqueue_frontend_assets');

// Load single event template
function simple_event_template($template) {
    if (is_singular('event')) {
        $custom_template = plugin_dir_path(__FILE__) . 'templates/single-event.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
}
add_filter('single_template', 'simple_event_template');

// Output Open Graph & Twitter Card meta tags for single event pages
function se_output_og_meta_tags() {
    if (!is_singular('event')) return;

    $event_id = get_the_ID();
    $title = get_the_title($event_id);
    $url = get_permalink($event_id);
    $image = get_the_post_thumbnail_url($event_id, 'large');
    $site_name = get_bloginfo('name');

    // Only use Short Description field (no fallback)
    $description = get_post_meta($event_id, '_se_event_short_description', true);
    $description = wp_strip_all_tags(trim($description));

    // Open Graph
    echo '<meta property="og:type" content="article" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";
    if ($image) {
        echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";
    }

    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
    if ($image) {
        echo '<meta name="twitter:image" content="' . esc_url($image) . '" />' . "\n";
    }
}
add_action('wp_head', 'se_output_og_meta_tags', 5);

// Create event_submissions table on plugin activation
register_activation_hook(__FILE__, 'se_create_event_submission_table');

function se_create_event_submission_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'event_submissions';
    $charset_collate = $wpdb->get_charset_collate();

    // Create table if not exists
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        company VARCHAR(100) DEFAULT NULL,
        job_title VARCHAR(100) DEFAULT NULL,
        custom_fields TEXT DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX (event_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql); // Safe to use multiple times

    // Add columns if not available (safety check)
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name", ARRAY_A);
    $existing_columns = array_column($columns, 'Field');

    $alter_queries = [];

    if (!in_array('phone', $existing_columns)) {
        $alter_queries[] = "ADD COLUMN phone VARCHAR(20) DEFAULT NULL";
    }
    if (!in_array('company', $existing_columns)) {
        $alter_queries[] = "ADD COLUMN company VARCHAR(100) DEFAULT NULL";
    }
    if (!in_array('job_title', $existing_columns)) {
        $alter_queries[] = "ADD COLUMN job_title VARCHAR(100) DEFAULT NULL";
    }
    if (!in_array('form_type', $existing_columns)) {
        $alter_queries[] = "ADD COLUMN form_type VARCHAR(20) NOT NULL DEFAULT 'registration'";
    }
    if (!in_array('custom_fields', $existing_columns)) {
        $alter_queries[] = "ADD COLUMN custom_fields TEXT DEFAULT NULL";
    }

    if (!empty($alter_queries)) {
        $wpdb->query("ALTER TABLE $table_name " . implode(", ", $alter_queries));
    }
}

// =============================================================================
// SCHEMA.ORG — Event JSON-LD
// Auto-generate Event schema dari meta fields yang tersedia.
// Injected via wp_head, hanya jalan di single event pages.
// =============================================================================

add_action('wp_head', 'se_output_event_schema', 10);

function se_output_event_schema() {
    if (!is_singular('event')) return;

    $post_id     = get_the_ID();
    $start_date  = get_post_meta($post_id, '_se_event_start_date',        true);
    $start_time  = get_post_meta($post_id, '_se_event_start_time',        true);
    $end_date    = get_post_meta($post_id, '_se_event_end_date',          true);
    $end_time    = get_post_meta($post_id, '_se_event_end_time',          true);
    $until_done  = get_post_meta($post_id, '_se_event_until_finished',    true);
    $meeting_url = get_post_meta($post_id, '_se_event_meeting_url',       true);
    $location    = get_post_meta($post_id, '_se_event_location',          true);
    $description = get_post_meta($post_id, '_se_event_short_description', true);
    $speakers    = get_post_meta($post_id, '_se_event_speakers',          true);
    $image       = get_the_post_thumbnail_url($post_id, 'full');

    // Wajib ada start date
    if (!$start_date) return;

    // ── Helper: gabungkan date + time ke ISO 8601 +07:00 ──────────────────
    $build_dt = function($date, $time = '') {
        $time      = $time ?: '00:00:00';
        $timestamp = strtotime($date . ' ' . $time);
        return $timestamp ? date('Y-m-d\TH:i:s+07:00', $timestamp) : null;
    };

    $start_dt = $build_dt($start_date, $start_time);
    if (!$start_dt) return;

    // End date: jika "Until Finished" dicentang, pakai start date (open-ended)
    $end_dt = $until_done ? $start_dt : $build_dt($end_date ?: $start_date, $end_time);

    // ── Schema dasar ───────────────────────────────────────────────────────
    $schema = [
        '@context'            => 'https://schema.org',
        '@type'               => 'Event',
        '@id'                 => get_permalink($post_id) . '#event',
        'name'                => get_the_title($post_id),
        'startDate'           => $start_dt,
        'endDate'             => $end_dt,
        'eventStatus'         => 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
        'url'                 => get_permalink($post_id),
        'inLanguage'          => 'id-ID',
        // Referensi ke Organization node yang sudah ada di Yoast @graph
        // Tidak perlu re-declare — cukup pointer via @id
        'organizer'           => [
            '@id' => 'https://idstar.co.id/#organization',
        ],
    ];

    // ── Description ────────────────────────────────────────────────────────
    if ($description) {
        $schema['description'] = wp_strip_all_tags(trim($description));
    }

    // ── Image ──────────────────────────────────────────────────────────────
    if ($image) {
        $schema['image'] = $image;
    }

    // ── Location ───────────────────────────────────────────────────────────
    // Prioritas: meeting URL (virtual) → lokasi fisik → fallback ke halaman
    if ($meeting_url) {
        $schema['location'] = [
            '@type' => 'VirtualLocation',
            'url'   => $meeting_url,
        ];
    } elseif ($location) {
        $schema['location'] = [
            '@type' => 'Place',
            'name'  => $location,
        ];
    } else {
        $schema['location'] = [
            '@type' => 'VirtualLocation',
            'url'   => get_permalink($post_id),
        ];
    }

    // ── Performers (speakers) ──────────────────────────────────────────────
    if (!empty($speakers) && is_array($speakers)) {
        $performers = [];
        foreach ($speakers as $speaker) {
            $name = '';

            // Handle berbagai format penyimpanan speaker
            if (is_array($speaker)) {
                $name = $speaker['name'] ?? $speaker['speaker_name'] ?? '';
            } elseif (is_string($speaker)) {
                $name = $speaker;
            }

            if ($name) {
                $performer = [
                    '@type' => 'Person',
                    'name'  => sanitize_text_field($name),
                ];

                // Tambah jabatan jika ada
                $title = $speaker['title'] ?? $speaker['speaker_title'] ?? $speaker['position'] ?? '';
                if ($title) {
                    $performer['jobTitle'] = sanitize_text_field($title);
                }

                $performers[] = $performer;
            }
        }

        if (!empty($performers)) {
            $schema['performer'] = count($performers) === 1 ? $performers[0] : $performers;
        }
    }

    // ── Output ─────────────────────────────────────────────────────────────
    echo '<script type="application/ld+json">'
        . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        . '</script>' . PHP_EOL;
}

// Initialize GitHub auto-updater
$se_updater = new SE_GitHub_Updater(__FILE__);
$se_updater->set_repository('Gioidstar', 'simple-event');