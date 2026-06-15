<?php
/**
 * Event List Shortcode
 *
 * Usage:
 *   [event_list]
 *   [event_list category="webinar" columns="3" per_page="6" status="upcoming"]
 *   [event_list category="workshop,seminar" columns="2" per_page="-1" status="all" orderby="date"]
 *
 * Attributes:
 *   category  - Event category slug (comma-separated for multiple). Empty = all.
 *   columns   - Number of grid columns: 1, 2, 3, 4 (default: 3)
 *   per_page  - Number of events per page. -1 = all, no pagination (default: -1)
 *   status    - Filter status: "upcoming", "past", "all" (default: "all")
 *   orderby   - Sort by: "date", "title" (default: "date")
 *   order     - ASC or DESC (default: "DESC")
 *   show_filter - Show category filter tabs: "yes" or "no" (default: "no")
 */

function se_event_list_shortcode($atts) {
    $atts = shortcode_atts([
        'category'    => '',
        'columns'     => 3,
        'per_page'    => -1,
        'status'      => 'all',
        'orderby'     => 'date',
        'order'       => 'DESC',
        'show_filter' => 'no',
    ], $atts, 'event_list');

    $columns  = max(1, min(4, intval($atts['columns'])));
    $per_page = intval($atts['per_page']);
    $status   = sanitize_text_field($atts['status']);
    $order    = strtoupper($atts['order']) === 'ASC' ? 'ASC' : 'DESC';
    $show_filter = $atts['show_filter'] === 'yes';

    // Pagination: get current page
    $paged = 1;
    if ($per_page > 0) {
        $paged = max(1, intval(get_query_var('paged') ?: get_query_var('page') ?: 1));
    }

    // Clean category - trim whitespace and filter empty values
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

    // Add paged if pagination is active
    if ($per_page > 0) {
        $args['paged'] = $paged;
    }

    // Orderby
    if ($atts['orderby'] === 'title') {
        $args['orderby'] = 'title';
        $args['order']   = $order;
    } else {
        $args['meta_key'] = '_se_event_start_date';
        $args['orderby']  = 'meta_value';
        $args['order']    = $order;
    }

    // Category filter (only if valid slugs exist)
    if (!empty($category_slugs)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'event_category',
                'field'    => 'slug',
                'terms'    => $category_slugs,
            ],
        ];
    }

    // Status filter (upcoming / past)
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

    // Prime meta cache for all queried posts (single query instead of N*6 queries)
    if ($query->have_posts()) {
        update_postmeta_cache(wp_list_pluck($query->posts, 'ID'));
    }

    ob_start();

    // Output CSS once
    se_output_event_grid_css();

    // Category filter tabs
    if ($show_filter) {
        $filter_cats = get_terms([
            'taxonomy'   => 'event_category',
            'hide_empty' => true,
        ]);
        if (!is_wp_error($filter_cats) && !empty($filter_cats)) {
            $grid_id = 'se-grid-' . wp_rand(1000, 9999);
            echo '<div class="se-filter-wrap" data-grid="' . $grid_id . '">';
            echo '<button class="se-filter-btn active" data-cat="all">All</button>';
            foreach ($filter_cats as $fcat) {
                echo '<button class="se-filter-btn" data-cat="' . esc_attr($fcat->slug) . '">' . esc_html($fcat->name) . '</button>';
            }
            echo '</div>';
        }
    }

    if ($query->have_posts()) {
        $grid_id_attr = ($show_filter && isset($grid_id)) ? ' id="' . $grid_id . '"' : '';
        echo '<div class="se-event-grid se-event-grid-' . $columns . '"' . $grid_id_attr . '>';

        while ($query->have_posts()) {
            $query->the_post();
            $event_id   = get_the_ID();
            $start_date = get_post_meta($event_id, '_se_event_start_date', true);
            $end_date   = get_post_meta($event_id, '_se_event_end_date', true);
            $end_time   = get_post_meta($event_id, '_se_event_end_time', true);
            $location   = get_post_meta($event_id, '_se_event_location', true);
            $replay_url = get_post_meta($event_id, '_se_event_replay_url', true);
            $thumbnail  = get_the_post_thumbnail_url($event_id, 'medium_large');

            // Determine event status
            if (empty($end_time)) $end_time = '23:59';
            $event_end_ts = strtotime(($end_date ?: $start_date) . ' ' . $end_time);
            $is_ended = $event_end_ts && current_time('timestamp') > $event_end_ts;
            $has_replay = $is_ended && !empty($replay_url);

            // Format date
            $display_date = !empty($start_date) ? date_i18n('d M Y', strtotime($start_date)) : '';

            // Category badges + data attribute for filter
            $categories = get_the_terms($event_id, 'event_category');
            $cat_slugs_data = '';
            if (!empty($categories) && !is_wp_error($categories)) {
                $cat_slugs_data = implode(',', wp_list_pluck($categories, 'slug'));
            }

            // Button
            if ($has_replay) {
                $btn_text  = 'Watch the Replay';
                $btn_class = 'se-btn-replay';
            } elseif ($is_ended) {
                $btn_text  = 'Event Has Ended';
                $btn_class = 'se-btn-ended';
            } else {
                $btn_text  = 'Register Now';
                $btn_class = 'se-btn-register';
            }
            ?>
            <div class="se-event-card" data-categories="<?php echo esc_attr($cat_slugs_data); ?>">
                <?php if ($thumbnail): ?>
                <div class="se-event-thumb">
                    <a href="<?php the_permalink(); ?>">
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
                    </a>
                    <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                    <div class="se-event-cats">
                        <?php foreach ($categories as $cat): ?>
                            <span class="se-event-cat"><?php echo esc_html($cat->name); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="se-event-body">
                    <h3 class="se-event-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <?php if ($display_date || $location): ?>
                    <div class="se-event-meta">
                        <?php if ($display_date): ?>
                            <span class="se-event-date"><?php echo esc_html($display_date); ?></span>
                        <?php endif; ?>
                        <?php if ($location): ?>
                            <span class="se-event-location"><?php echo esc_html($location); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="se-event-action">
                        <?php if ($is_ended && !$has_replay): ?>
                            <span class="se-event-btn <?php echo $btn_class; ?>"><?php echo esc_html($btn_text); ?></span>
                        <?php else: ?>
                            <a href="<?php the_permalink(); ?>" class="se-event-btn <?php echo $btn_class; ?>"><?php echo esc_html($btn_text); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        }

        echo '</div>';

        // Pagination
        if ($per_page > 0 && $query->max_num_pages > 1) {
            echo '<div class="se-pagination">';
            echo paginate_links([
                'total'   => $query->max_num_pages,
                'current' => $paged,
                'format'  => 'page/%#%/',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            ]);
            echo '</div>';
        }
    } else {
        echo '<p class="se-no-events">No events found.</p>';
    }

    // Filter JS (only once)
    if ($show_filter) {
        se_output_filter_js();
    }

    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('event_list', 'se_event_list_shortcode');

/**
 * Output event grid CSS inline (only once per page).
 */
function se_output_event_grid_css() {
    static $outputted = false;
    if ($outputted) return;
    $outputted = true;
    ?>
    <style>
    .se-event-grid{display:grid;gap:24px}
    .se-event-grid-1{grid-template-columns:1fr}
    .se-event-grid-2{grid-template-columns:repeat(2,1fr)}
    .se-event-grid-3{grid-template-columns:repeat(3,1fr)}
    .se-event-grid-4{grid-template-columns:repeat(4,1fr)}
    @media(max-width:768px){
        .se-event-grid-2,.se-event-grid-3,.se-event-grid-4{grid-template-columns:1fr}
    }
    @media(min-width:769px) and (max-width:1024px){
        .se-event-grid-3,.se-event-grid-4{grid-template-columns:repeat(2,1fr)}
    }
    .se-event-card{
        background:#fff;
        border-radius:12px;
        overflow:hidden;
        box-shadow:0 2px 12px rgba(0,0,0,0.08);
        border:1px solid #f0f0f0;
        transition:transform 0.2s,box-shadow 0.2s;
        display:flex;
        flex-direction:column;
    }
    .se-event-card:hover{
        transform:translateY(-4px);
        box-shadow:0 8px 24px rgba(0,0,0,0.12);
    }
    .se-event-thumb{position:relative;overflow:hidden}
    .se-event-thumb img{
        width:100%;
        height:200px;
        object-fit:cover;
        display:block;
        transition:transform 0.3s;
    }
    .se-event-card:hover .se-event-thumb img{transform:scale(1.05)}
    .se-event-cats{
        position:absolute;
        top:10px;
        left:10px;
        display:flex;
        gap:6px;
        flex-wrap:wrap;
    }
    .se-event-cat{
        background:rgba(234,36,42,0.9);
        color:#fff;
        font-size:11px;
        font-weight:600;
        padding:3px 10px;
        border-radius:20px;
        text-transform:uppercase;
        letter-spacing:0.5px;
    }
    .se-event-body{
        padding:20px;
        flex:1;
        display:flex;
        flex-direction:column;
    }
    .se-event-title{
        font-size:1.1rem;
        font-weight:700;
        margin:0 0 10px;
        line-height:1.4;
    }
    .se-event-title a{color:#1a1a2e;text-decoration:none}
    .se-event-title a:hover{color:#EA242A}
    .se-event-meta{
        display:flex;
        flex-direction:column;
        gap:4px;
        margin-bottom:16px;
        font-size:0.85rem;
        color:#666;
    }
    .se-event-date::before{content:"\1F4C5 "}
    .se-event-location::before{content:"\1F4CD "}
    .se-event-action{margin-top:auto}
    .se-event-btn{
        display:inline-block;
        padding:10px 20px;
        border-radius:6px;
        font-size:14px;
        font-weight:600;
        text-decoration:none;
        text-align:center;
        transition:opacity 0.2s;
        cursor:pointer;
    }
    .se-event-btn:hover{opacity:0.85}
    .se-btn-register{background:#EA242A;color:#fff}
    .se-btn-register:hover{color:#fff}
    .se-btn-replay{background:#2563EB;color:#fff}
    .se-btn-replay:hover{color:#fff}
    .se-btn-ended{background:#9ca3af;color:#fff;cursor:default}
    .se-btn-ended:hover{opacity:1;color:#fff}
    .se-no-events{text-align:center;color:#888;padding:40px 20px}
    /* Filter buttons */
    .se-filter-wrap{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        margin-bottom:24px;
    }
    .se-filter-btn{
        padding:8px 20px;
        border:2px solid #e5e7eb;
        border-radius:50px;
        background:#fff;
        color:#374151;
        font-size:14px;
        font-weight:500;
        cursor:pointer;
        transition:all 0.2s;
    }
    .se-filter-btn:hover{
        border-color:#EA242A;
        color:#EA242A;
    }
    .se-filter-btn.active{
        background:#EA242A;
        border-color:#EA242A;
        color:#fff;
    }
    .se-event-card.se-hidden{
        display:none;
    }
    /* Pagination */
    .se-pagination{
        display:flex;
        justify-content:center;
        align-items:center;
        gap:4px;
        margin-top:32px;
        flex-wrap:wrap;
    }
    .se-pagination .page-numbers{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:40px;
        height:40px;
        padding:0 12px;
        border:2px solid #e5e7eb;
        border-radius:8px;
        background:#fff;
        color:#374151;
        font-size:14px;
        font-weight:500;
        text-decoration:none;
        transition:all 0.2s;
    }
    .se-pagination .page-numbers:hover{
        border-color:#EA242A;
        color:#EA242A;
    }
    .se-pagination .page-numbers.current{
        background:#EA242A;
        border-color:#EA242A;
        color:#fff;
    }
    .se-pagination .page-numbers.dots{
        border:none;
        background:none;
        cursor:default;
    }
    .se-pagination .page-numbers.dots:hover{
        color:#374151;
    }
    .se-pagination .page-numbers.prev,
    .se-pagination .page-numbers.next{
        font-weight:700;
    }
    </style>
    <?php
}

/**
 * Output filter JS inline (only once per page).
 */
function se_output_filter_js() {
    static $js_outputted = false;
    if ($js_outputted) return;
    $js_outputted = true;
    ?>
    <script>
    (function(){
        document.querySelectorAll('.se-filter-wrap').forEach(function(wrap){
            var gridId = wrap.getAttribute('data-grid');
            var grid = document.getElementById(gridId);
            if(!grid) return;
            var btns = wrap.querySelectorAll('.se-filter-btn');
            var cards = grid.querySelectorAll('.se-event-card');
            btns.forEach(function(btn){
                btn.addEventListener('click', function(){
                    btns.forEach(function(b){ b.classList.remove('active'); });
                    btn.classList.add('active');
                    var cat = btn.getAttribute('data-cat');
                    cards.forEach(function(card){
                        if(cat === 'all'){
                            card.classList.remove('se-hidden');
                        } else {
                            var cardCats = (card.getAttribute('data-categories') || '').split(',');
                            if(cardCats.indexOf(cat) !== -1){
                                card.classList.remove('se-hidden');
                            } else {
                                card.classList.add('se-hidden');
                            }
                        }
                    });
                });
            });
        });
    })();
    </script>
    <?php
}
