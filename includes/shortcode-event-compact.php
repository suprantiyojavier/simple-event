<?php
/**
 * Event Compact Shortcode
 *
 * Usage:
 *   [event_compact]
 *   [event_compact columns="2" per_page="4" status="upcoming"]
 *
 * Attributes:
 *   category  - Event category slug (comma-separated for multiple). Empty = all.
 *   columns   - Number of grid columns: 1, 2, 3, 4, 5, 6 (default: 2)
 *   per_page  - Number of events displayed. -1 = all (default: 4)
 *   status    - Filter status: "upcoming", "past", "all" (default: "all")
 *   orderby   - Sort by: "date", "title" (default: "date")
 *   order     - ASC or DESC (default: "DESC")
 */

function se_event_compact_shortcode($atts) {
    $atts = shortcode_atts([
        'category' => '',
        'columns'  => 2,
        'per_page' => 4,
        'status'   => 'all',
        'orderby'  => 'date',
        'order'    => 'DESC',
    ], $atts, 'event_compact');

    $columns  = max(1, min(6, intval($atts['columns'])));
    $per_page = intval($atts['per_page']);
    $status   = sanitize_text_field($atts['status']);
    $order    = strtoupper($atts['order']) === 'ASC' ? 'ASC' : 'DESC';

    $category_raw = trim($atts['category']);
    $category_slugs = [];
    if (!empty($category_raw)) {
        $category_slugs = array_filter(array_map('trim', explode(',', $category_raw)));
    }

    // Build query args
    $args = [
        'post_type'      => 'event',
        'posts_per_page' => $per_page,
        'post_status'    => 'publish',
        'ignore_sticky_posts' => true,
    ];

    if ($atts['orderby'] === 'title') {
        $args['orderby'] = 'title';
        $args['order']   = $order;
    } else {
        $args['meta_key'] = '_se_event_start_date';
        $args['orderby']  = 'meta_value';
        $args['order']    = $order;
    }

    if (!empty($category_slugs)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'event_category',
                'field'    => 'slug',
                'terms'    => $category_slugs,
            ],
        ];
    }

    if ($status === 'upcoming') {
        $args['meta_query'] = [
            [
                'key'     => '_se_event_end_date',
                'value'   => current_time('Y-m-d'),
                'compare' => '>=',
                'type'    => 'DATE',
            ],
        ];
    } elseif ($status === 'past') {
        $args['meta_query'] = [
            [
                'key'     => '_se_event_end_date',
                'value'   => current_time('Y-m-d'),
                'compare' => '<',
                'type'    => 'DATE',
            ],
        ];
    }

    $query = new WP_Query($args);

    // Prime meta cache for all queried posts (single query instead of N*5 queries)
    if ($query->have_posts()) {
        update_postmeta_cache(wp_list_pluck($query->posts, 'ID'));
    }

    ob_start();

    se_output_event_compact_css();

    if ($query->have_posts()) {
        echo '<div class="se-compact-grid se-compact-grid-' . $columns . '">';

        while ($query->have_posts()) {
            $query->the_post();
            $event_id   = get_the_ID();
            $start_date = get_post_meta($event_id, '_se_event_start_date', true);
            $end_date   = get_post_meta($event_id, '_se_event_end_date', true);
            $end_time   = get_post_meta($event_id, '_se_event_end_time', true);
            $replay_url = get_post_meta($event_id, '_se_event_replay_url', true);
            $thumbnail  = get_the_post_thumbnail_url($event_id, 'medium_large');

            // Determine event status
            if (empty($end_time)) $end_time = '23:59';
            $event_end_ts = strtotime(($end_date ?: $start_date) . ' ' . $end_time);
            $is_ended = $event_end_ts && current_time('timestamp') > $event_end_ts;
            $has_replay = $is_ended && !empty($replay_url);

            // Button
            if ($has_replay) {
                $btn_text  = 'Watch the Replay';
                $btn_class = 'se-compact-btn-replay';
            } elseif ($is_ended) {
                $btn_text  = 'Event Has Ended';
                $btn_class = 'se-compact-btn-ended';
            } else {
                $btn_text  = 'Register Now';
                $btn_class = 'se-compact-btn-register';
            }
            ?>
            <div class="se-compact-card">
                <?php if ($thumbnail): ?>
                <div class="se-compact-thumb">
                    <a href="<?php the_permalink(); ?>">
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
                    </a>
                </div>
                <?php endif; ?>
                <div class="se-compact-body">
                    <h4 class="se-compact-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                    <div class="se-compact-action">
                        <?php if ($is_ended && !$has_replay): ?>
                            <span class="se-compact-btn <?php echo $btn_class; ?>"><?php echo esc_html($btn_text); ?></span>
                        <?php else: ?>
                            <a href="<?php the_permalink(); ?>" class="se-compact-btn <?php echo $btn_class; ?>"><?php echo esc_html($btn_text); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        }

        echo '</div>';
    } else {
        echo '<p class="se-no-events">No events found.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('event_compact', 'se_event_compact_shortcode');

/**
 * Output compact grid CSS inline (only once per page).
 */
function se_output_event_compact_css() {
    static $outputted = false;
    if ($outputted) return;
    $outputted = true;
    ?>
    <style>
    .se-compact-grid{display:grid;gap:20px}
    .se-compact-grid-1{grid-template-columns:1fr}
    .se-compact-grid-2{grid-template-columns:repeat(2,1fr)}
    .se-compact-grid-3{grid-template-columns:repeat(3,1fr)}
    .se-compact-grid-4{grid-template-columns:repeat(4,1fr)}
    .se-compact-grid-5{grid-template-columns:repeat(5,1fr)}
    .se-compact-grid-6{grid-template-columns:repeat(6,1fr)}
    @media(max-width:768px){
        .se-compact-grid-2,.se-compact-grid-3,.se-compact-grid-4,.se-compact-grid-5,.se-compact-grid-6{grid-template-columns:1fr}
    }
    @media(min-width:769px) and (max-width:1024px){
        .se-compact-grid-3,.se-compact-grid-4,.se-compact-grid-5,.se-compact-grid-6{grid-template-columns:repeat(2,1fr)}
    }
    .se-compact-card{
        background:#fff;
        border-radius:12px;
        overflow:hidden;
        box-shadow:0 2px 12px rgba(0,0,0,0.08);
        border:1px solid #f0f0f0;
        transition:transform 0.2s,box-shadow 0.2s;
        display:flex;
        flex-direction:column;
    }
    .se-compact-card:hover{
        transform:translateY(-4px);
        box-shadow:0 8px 24px rgba(0,0,0,0.12);
    }
    .se-compact-thumb{overflow:hidden}
    .se-compact-thumb img{
        width:100%;
        height:180px;
        object-fit:cover;
        display:block;
        transition:transform 0.3s;
    }
    .se-compact-card:hover .se-compact-thumb img{transform:scale(1.05)}
    .se-compact-body{
        padding:16px;
        flex:1;
        display:flex;
        flex-direction:column;
    }
    .se-compact-title{
        font-size:0.95rem;
        font-weight:700;
        margin:0 0 12px;
        line-height:1.4;
    }
    .se-compact-title a{color:#1a1a2e;text-decoration:none}
    .se-compact-title a:hover{color:#EA242A}
    .se-compact-action{margin-top:auto}
    .se-compact-btn{
        display:inline-block;
        padding:8px 16px;
        border-radius:6px;
        font-size:13px;
        font-weight:600;
        text-decoration:none;
        text-align:center;
        transition:opacity 0.2s;
        cursor:pointer;
    }
    .se-compact-btn:hover{opacity:0.85}
    .se-compact-btn-register{background:#EA242A;color:#fff}
    .se-compact-btn-register:hover{color:#fff}
    .se-compact-btn-replay{background:#2563EB;color:#fff}
    .se-compact-btn-replay:hover{color:#fff}
    .se-compact-btn-ended{background:#9ca3af;color:#fff;cursor:default}
    .se-compact-btn-ended:hover{opacity:1;color:#fff}
    </style>
    <?php
}
