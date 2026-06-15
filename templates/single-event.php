<?php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
    // Get event meta data
    $event_start_date = get_post_meta(get_the_ID(), '_se_event_start_date', true);
    $event_start_time = get_post_meta(get_the_ID(), '_se_event_start_time', true);
    $event_end_date = get_post_meta(get_the_ID(), '_se_event_end_date', true);
    $event_end_time = get_post_meta(get_the_ID(), '_se_event_end_time', true);
    $event_location = get_post_meta(get_the_ID(), '_se_event_location', true);
    $event_image = get_the_post_thumbnail_url(get_the_ID(), 'large');
    $replay_url = get_post_meta(get_the_ID(), '_se_event_replay_url', true);
    $has_replay = $replay_url && !empty(trim($replay_url));
    $speakers = get_post_meta(get_the_ID(), '_se_event_speakers', true);
    if (!is_array($speakers)) $speakers = [];
    $target_audiences = get_post_meta(get_the_ID(), '_se_event_target_audience', true);
    if (!is_array($target_audiences)) $target_audiences = [];
    $google_form_url = get_post_meta(get_the_ID(), '_se_event_google_form_url', true);
    $form_title = get_post_meta(get_the_ID(), '_se_event_form_title', true);
    $form_subtitle = get_post_meta(get_the_ID(), '_se_event_form_subtitle', true);
    $until_finished = get_post_meta(get_the_ID(), '_se_event_until_finished', true);

    // Check if event has ended
    $is_event_ended = false;
    $current_datetime = current_time('timestamp');

    // Set default end time if not available
    if (empty($event_end_time)) $event_end_time = '23:59';

    // Create timestamp for end date
    $event_end_timestamp = strtotime($event_end_date . ' ' . $event_end_time);

    // If end date is empty, use start date
    if (empty($event_end_date) && !empty($event_start_date)) {
        $event_end_date = $event_start_date;
        $event_end_timestamp = strtotime($event_start_date . ' ' . $event_end_time);
    }

    // Check if event has ended
    if (!empty($event_end_timestamp) && $current_datetime > $event_end_timestamp) {
        $is_event_ended = true;
    }

    // Format display date
    $display_date = !empty($event_start_date) ? date_i18n('l, d F Y', strtotime($event_start_date)) : '';
    // Format end date display for "Until Finished"
    $display_end_date = $until_finished ? 'Finished' : (!empty($event_end_date) ? date_i18n('l, d F Y', strtotime($event_end_date)) : '');
?>

<!-- Styles loaded via se-single-event.css -->

<div class="se-event-wrap">
    <!-- Banner Overhaul -->
    <div class="se-cta-container">
        <div class="se-cta-left">
            <?php
            // Get custom taxonomies for badge
            $category_badge = get_post_meta(get_the_ID(), '_se_event_badge_text', true);
            if (empty($category_badge)) {
                $category_badge = '• EVENT';
            }
            $categories = get_the_terms(get_the_ID(), 'event_category');
            if (!empty($categories) && !is_wp_error($categories)) {
                $badge_parts = [];
                $first = true;
                foreach ($categories as $cat) {
                    if ($first) {
                        $badge_parts[] = '• ' . strtoupper($cat->name);
                        $first = false;
                    } else {
                        $badge_parts[] = '#' . strtoupper(str_replace(' ', '', $cat->name));
                    }
                }
                if (count($badge_parts) > 1) {
                    $category_badge = $badge_parts[0] . ' | ' . implode(' ', array_slice($badge_parts, 1));
                } else {
                    $category_badge = $badge_parts[0];
                }
            }
            ?>
            <div class="se-badge-pill"><?php echo esc_html($category_badge); ?></div>
            
            <h1 class="se-cta-title"><?php the_title(); ?></h1>
            
            <?php
            // Get short description
            $event_desc = get_post_meta(get_the_ID(), '_se_event_short_description', true);
            if (empty($event_desc)) {
                $event_desc = wp_trim_words(get_the_excerpt(), 28, '...');
            }
            ?>
            <p class="se-cta-desc"><?php echo nl2br(esc_html($event_desc)); ?></p>
            
            <div class="se-info-bar">
                <div class="se-info-item">
                    <div class="se-info-icon-box">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <span class="se-info-text"><?php echo esc_html(date_i18n('j F Y', strtotime($event_start_date))); ?></span>
                </div>
                
                <div class="se-info-item">
                    <div class="se-info-icon-box">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                    <span class="se-info-text">
                        <?php if (!$until_finished): ?>
                            <?php echo esc_html($event_start_time); ?> - <?php echo esc_html($event_end_time); ?>
                        <?php else: ?>
                            <?php echo esc_html($event_start_time); ?> - Selesai
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="se-info-item">
                    <div class="se-info-icon-box">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    </div>
                    <span class="se-info-text"><?php echo esc_html($event_location ?: 'Online'); ?></span>
                </div>
            </div>
            
            <div class="se-action-bar">
                <?php if ($is_event_ended && $has_replay): ?>
                    <a href="#se-form-section" class="se-btn-watch">▶ Watch Replay</a>
                <?php elseif ($is_event_ended): ?>
                    <button class="se-btn-ended" disabled>Event Has Ended</button>
                <?php else: ?>
                    <a href="#se-form-section" class="se-btn-watch">Register Now</a>
                <?php endif; ?>
                
                <a href="#se-description-section" class="se-link-details">See Details →</a>
            </div>
        </div>
        
        <div class="se-cta-right">
            <?php if ($event_image): ?>
                <div class="se-image-wrapper">
                    <img src="<?php echo esc_url($event_image); ?>" alt="<?php the_title(); ?>" class="se-image-card">
                </div>
            <?php endif; ?>
        </div>

    <!-- Share buttons (Bottom of banner) -->
    <div class="se-share-container">
        <div class="se-share-inner">
            <p class="se-share-label">Share Event:</p>
            <div id="se-share-buttons" class="se-share-wrap">
                <a href="#" data-share="facebook" title="Facebook" class="se-share-btn se-share-btn--fb">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <a href="#" data-share="linkedin" title="LinkedIn" class="se-share-btn se-share-btn--li">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                </a>
                <a href="#" data-share="whatsapp" title="WhatsApp" class="se-share-btn se-share-btn--wa">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </a>
                <a href="#" data-share="twitter" title="X (Twitter)" class="se-share-btn se-share-btn--tw">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <button data-share="copy" title="Copy Link" class="se-share-btn se-share-btn--copy">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    <span id="se-copy-tooltip" class="se-copy-tooltip">Link copied!</span>
                </button>
            </div>
        </div>
        <script>
        (function(){
            var container = document.getElementById('se-share-buttons');
            if (!container) return;

            var shortDesc = <?php echo json_encode(get_post_meta(get_the_ID(), '_se_event_short_description', true) ?: ''); ?>;
            var url = window.location.href;
            var title = document.title;

            var eu = encodeURIComponent(url);
            var et = encodeURIComponent(title);
            var ed = shortDesc ? encodeURIComponent(shortDesc) : '';
            var fullText = et + (ed ? '%0A' + ed : '');

            container.addEventListener('click', function(e) {
                var btn = e.target.closest('[data-share]');
                if (!btn) return;
                e.preventDefault();
                var type = btn.getAttribute('data-share');
                var shareUrl = '';

                switch(type) {
                    case 'facebook':
                        shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + eu;
                        break;
                    case 'linkedin':
                        shareUrl = 'https://www.linkedin.com/feed/?shareActive=true&text=' + fullText + '%0A' + eu;
                        break;
                    case 'whatsapp':
                        shareUrl = 'https://wa.me/?text=' + fullText + '%0A' + eu;
                        break;
                    case 'twitter':
                        shareUrl = 'https://twitter.com/intent/tweet?text=' + fullText + '&url=' + eu;
                        break;
                    case 'copy':
                        var tmp = document.createElement('textarea');
                        tmp.value = url;
                        document.body.appendChild(tmp);
                        tmp.select();
                        document.execCommand('copy');
                        document.body.removeChild(tmp);
                        var tip = document.getElementById('se-copy-tooltip');
                        if (tip) { tip.style.opacity='1'; setTimeout(function(){ tip.style.opacity='0'; }, 2000); }
                        return;
                }
                if (shareUrl) window.open(shareUrl, '_blank', 'noopener,width=600,height=500');
            });
        })();
        </script>
    </div>
</div>

    <!-- Description -->
    <div id="se-description-section" style="margin-top: 3rem; scroll-margin-top: 4.5rem;">
        <div style="background-color: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; line-height: 1.7; color: #334155;">
            <p> <?php the_content(); ?></p>
        </div>        </div>

        <?php if (!empty($speakers)): ?>
        <div style="margin-top: 2rem;">
            <h2 style="font-size: 2rem; text-align: center; margin-bottom: 0.5rem; font-weight: 600; color: #1a1a2e;">Speakers & Moderators</h2>
            <div style="width: 60px; height: 3px; background: #ea242a; margin: 0 auto 1.5rem;"></div>
            <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 1.5rem;">
                <?php foreach ($speakers as $spk):
                    $photo_url = !empty($spk['photo_id']) ? wp_get_attachment_image_url($spk['photo_id'], 'medium') : '';
                    $role_raw = $spk['role'] ?? 'speaker';
                    $role_label = ucwords($role_raw);
                ?>
                <div style="background: #fff; border-radius: 12px; padding: 1.5rem; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #f0f0f0; flex: 0 1 240px; min-width: 220px; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='none'">
                    <div style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; margin: 0 auto 1rem; background: #eee;">
                        <?php if ($photo_url): ?>
                            <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($spk['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                    <h3 style="font-size: 1.1rem; margin: 0 0 0.3rem; color: #1f2937;"><?php echo esc_html($spk['name']); ?></h3>
                    <p style="font-size: 0.85rem; color: #6b7280; margin: 0 0 0.5rem; line-height: 1.4;"><?php echo esc_html($spk['job_title']); ?></p>
                    <span style="font-size: 0.8rem; color: #888;"><?php echo esc_html($role_label); ?></span>
                    <?php if (!empty($spk['linkedin']) || !empty($spk['instagram']) || !empty($spk['twitter']) || !empty($spk['facebook'])): ?>
                    <div style="display:flex; gap:8px; justify-content:center; margin-top:10px;">
                        <?php if (!empty($spk['linkedin'])): ?>
                        <a href="<?php echo esc_url($spk['linkedin']); ?>" target="_blank" title="LinkedIn" style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:50%; background:#0A66C2; color:#fff; text-decoration:none; transition:opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($spk['instagram'])): ?>
                        <a href="<?php echo esc_url($spk['instagram']); ?>" target="_blank" title="Instagram" style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:50%; background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888); color:#fff; text-decoration:none; transition:opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($spk['twitter'])): ?>
                        <a href="<?php echo esc_url($spk['twitter']); ?>" target="_blank" title="X (Twitter)" style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:50%; background:#000; color:#fff; text-decoration:none; transition:opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($spk['facebook'])): ?>
                        <a href="<?php echo esc_url($spk['facebook']); ?>" target="_blank" title="Facebook" style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:50%; background:#1877F2; color:#fff; text-decoration:none; transition:opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Target Audience -->
        <?php if (!empty($target_audiences)): ?>
        <div style="margin-top: 2rem;">
            <h2 style="font-size: 1.5rem; text-align: center; margin-bottom: 0.5rem;">Target Audience</h2>
            <div style="width: 60px; height: 3px; background: #333; margin: 0 auto 1.5rem;"></div>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;">
                <?php foreach ($target_audiences as $audience): ?>
                <span style="background: #f0f4ff; color: #1e40af; padding: 8px 18px; border-radius: 20px; font-size: 0.95rem; font-weight: 500; border: 1px solid #dbeafe;"><?php echo esc_html($audience); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Form Section (inline, no modal) -->
        <div id="se-form-section" style="margin-top: 3rem; scroll-margin-top: 2rem; border: 1px solid #e5e7eb; border-radius: 16px; padding: 2.5rem; background: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">
            <?php if ($is_event_ended && $has_replay): ?>
                <?php
                    $replay_title = !empty($form_title) ? $form_title : 'Watch the Replay';
                    $replay_subtitle = !empty($form_subtitle) ? $form_subtitle : '';
                ?>
                <h2 style="font-size: 2rem; text-align: center; margin-bottom: 0.5rem; font-weight: 600; color: #1a1a2e;"><?php echo esc_html($replay_title); ?></h2>
                <?php if (!empty($replay_subtitle)): ?>
                    <p style="text-align: center; color: #555; max-width: 700px; margin: 0 auto 1.5rem; line-height: 1.6;"><?php echo nl2br(esc_html($replay_subtitle)); ?></p>
                <?php endif; ?>
                <div style="width: 60px; height: 3px; background: #2563EB; margin: 0 auto 1.5rem;"></div>
                <?php echo do_shortcode('[event_replay_form id="' . get_the_ID() . '"]'); ?>
            <?php elseif ($is_event_ended): ?>
                <h2 style="font-size: 1.5rem; text-align: center;">Registration</h2>
                <div style="background-color: #f9f9f9; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                    <p style="text-align: center;">This event has ended and registration is closed.</p>
                </div>
            <?php else: ?>
                <?php
                    $reg_title = !empty($form_title) ? $form_title : 'Registration Form';
                    $reg_subtitle = !empty($form_subtitle) ? $form_subtitle : '';
                ?>
                <h2 style="font-size: 2rem; text-align: center; margin-bottom: 0.5rem; font-weight: 600; color: #1a1a2e;"><?php echo esc_html($reg_title); ?></h2>
                <?php if (!empty($reg_subtitle)): ?>
                    <p style="text-align: center; color: #555; max-width: 700px; margin: 0 auto 1.5rem; line-height: 1.6;"><?php echo nl2br(esc_html($reg_subtitle)); ?></p>
                <?php endif; ?>
                <div style="width: 60px; height: 3px; background: #EA242A; margin: 0 auto 1.5rem;"></div>
                <?php if (!empty($google_form_url)): ?>
                    <?php
                        // Ensure the Google Form URL has the embedded=true parameter
                        $embed_url = $google_form_url;
                        if (strpos($embed_url, 'embedded=true') === false) {
                            $embed_url .= (strpos($embed_url, '?') !== false ? '&' : '?') . 'embedded=true';
                        }
                    ?>
                    <iframe src="<?php echo esc_url($embed_url); ?>" width="100%" height="800" frameborder="0" marginheight="0" marginwidth="0" style="border:none; border-radius:8px;">Loading…</iframe>
                <?php else: ?>
                    <?php echo do_shortcode('[event_registration_form id="' . get_the_ID() . '"]'); ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Smooth scroll to sections -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('a[href="#se-form-section"], a[href="#se-description-section"]').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('href').substring(1);
            var targetEl = document.getElementById(targetId);
            if (targetEl) {
                targetEl.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
</script>

<?php
    endwhile;
endif;

get_footer();
?>
