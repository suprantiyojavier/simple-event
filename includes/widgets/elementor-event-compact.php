<?php
if (!defined('ABSPATH')) exit;

/**
 * Elementor Event Compact Widget
 * Displays events in a compact card layout: image, title, button only.
 */
class SE_Elementor_Event_Compact_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'se_event_compact';
    }

    public function get_title() {
        return 'Event Compact';
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return ['general'];
    }

    public function get_keywords() {
        return ['event', 'compact', 'mini', 'card', 'simple event'];
    }

    protected function register_controls() {

        // Content Section
        $this->start_controls_section('section_content', [
            'label' => 'Content',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $categories = get_terms([
            'taxonomy'   => 'event_category',
            'hide_empty' => false,
        ]);
        $cat_options = ['' => 'All Categories'];
        if (!is_wp_error($categories)) {
            foreach ($categories as $cat) {
                $cat_options[$cat->slug] = $cat->name;
            }
        }

        $this->add_control('category', [
            'label'   => 'Event Category',
            'type'    => \Elementor\Controls_Manager::SELECT2,
            'options' => $cat_options,
            'default' => '',
            'multiple' => true,
            'label_block' => true,
        ]);

        $this->add_control('status', [
            'label'   => 'Event Status',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'all'      => 'All',
                'upcoming' => 'Upcoming',
                'past'     => 'Past / Replay',
            ],
            'default' => 'all',
        ]);

        $this->add_control('per_page', [
            'label'   => 'Number of Events',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 4,
            'min'     => -1,
            'max'     => 50,
            'description' => '-1 to show all',
        ]);

        $this->add_control('orderby', [
            'label'   => 'Sort By',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'date'  => 'Event Date',
                'title' => 'Title',
            ],
            'default' => 'date',
        ]);

        $this->add_control('order', [
            'label'   => 'Order',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'DESC' => 'Newest First',
                'ASC'  => 'Oldest First',
            ],
            'default' => 'DESC',
        ]);

        $this->end_controls_section();

        // Layout Section
        $this->start_controls_section('section_layout', [
            'label' => 'Layout',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_responsive_control('columns', [
            'label'   => 'Columns',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '6' => '6',
            ],
            'default' => '2',
            'tablet_default' => '2',
            'mobile_default' => '1',
            'selectors' => [
                '{{WRAPPER}} .se-compact-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
            ],
        ]);

        $this->add_control('gap', [
            'label'   => 'Gap',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => ['px' => ['min' => 0, 'max' => 60]],
            'default' => ['size' => 20, 'unit' => 'px'],
            'selectors' => [
                '{{WRAPPER}} .se-compact-grid' => 'gap: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();

        // Style: Card
        $this->start_controls_section('section_style_card', [
            'label' => 'Card',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('card_border_radius', [
            'label'   => 'Border Radius',
            'type'    => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'default' => [
                'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12',
                'unit' => 'px', 'isLinked' => true,
            ],
            'selectors' => [
                '{{WRAPPER}} .se-compact-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'card_shadow',
            'selector' => '{{WRAPPER}} .se-compact-card',
        ]);

        $this->add_control('image_height', [
            'label'   => 'Image Height',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => ['px' => ['min' => 80, 'max' => 400]],
            'default' => ['size' => 180, 'unit' => 'px'],
            'selectors' => [
                '{{WRAPPER}} .se-compact-thumb img' => 'height: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();

        // Style: Button
        $this->start_controls_section('section_style_button', [
            'label' => 'Button',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('btn_register_color', [
            'label'   => 'Register Button Color',
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => '#EA242A',
            'selectors' => [
                '{{WRAPPER}} .se-compact-btn-register' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('btn_replay_color', [
            'label'   => 'Replay Button Color',
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => '#2563EB',
            'selectors' => [
                '{{WRAPPER}} .se-compact-btn-replay' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('btn_border_radius', [
            'label'   => 'Button Border Radius',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => ['px' => ['min' => 0, 'max' => 30]],
            'default' => ['size' => 6, 'unit' => 'px'],
            'selectors' => [
                '{{WRAPPER}} .se-compact-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $category = '';
        if (!empty($settings['category']) && is_array($settings['category'])) {
            $category = implode(',', array_filter($settings['category']));
        }

        $columns = $settings['columns'] ?? 2;

        $shortcode = sprintf(
            '[event_compact category="%s" columns="%s" per_page="%s" status="%s" orderby="%s" order="%s"]',
            esc_attr($category),
            esc_attr($columns),
            esc_attr($settings['per_page'] ?? 4),
            esc_attr($settings['status'] ?? 'all'),
            esc_attr($settings['orderby'] ?? 'date'),
            esc_attr($settings['order'] ?? 'DESC')
        );

        echo do_shortcode($shortcode);
    }
}
