<?php
if (!defined('ABSPATH')) exit;

/**
 * Elementor Event Grid Widget
 */
class SE_Elementor_Event_Grid_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'se_event_grid';
    }

    public function get_title() {
        return 'Event Grid';
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    public function get_categories() {
        return ['general'];
    }

    public function get_keywords() {
        return ['event', 'grid', 'list', 'simple event'];
    }

    protected function register_controls() {

        // Content Section
        $this->start_controls_section('section_content', [
            'label' => 'Content',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        // Category filter
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
            'default' => 6,
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

        $this->add_control('show_filter', [
            'label'   => 'Show Category Filter',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'label_on'  => 'Yes',
            'label_off' => 'No',
            'return_value' => 'yes',
            'default' => 'no',
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
            ],
            'default' => '3',
            'tablet_default' => '2',
            'mobile_default' => '1',
            'selectors' => [
                '{{WRAPPER}} .se-event-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
            ],
        ]);

        $this->add_control('gap', [
            'label'   => 'Gap',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => ['px' => ['min' => 0, 'max' => 60]],
            'default' => ['size' => 24, 'unit' => 'px'],
            'selectors' => [
                '{{WRAPPER}} .se-event-grid' => 'gap: {{SIZE}}{{UNIT}};',
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
                '{{WRAPPER}} .se-event-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'card_shadow',
            'selector' => '{{WRAPPER}} .se-event-card',
        ]);

        $this->add_control('image_height', [
            'label'   => 'Image Height',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => ['px' => ['min' => 100, 'max' => 400]],
            'default' => ['size' => 200, 'unit' => 'px'],
            'selectors' => [
                '{{WRAPPER}} .se-event-thumb img' => 'height: {{SIZE}}{{UNIT}};',
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
                '{{WRAPPER}} .se-btn-register' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('btn_replay_color', [
            'label'   => 'Replay Button Color',
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => '#2563EB',
            'selectors' => [
                '{{WRAPPER}} .se-btn-replay' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('btn_border_radius', [
            'label'   => 'Button Border Radius',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => ['px' => ['min' => 0, 'max' => 30]],
            'default' => ['size' => 6, 'unit' => 'px'],
            'selectors' => [
                '{{WRAPPER}} .se-event-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
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

        $columns = $settings['columns'] ?? 3;

        $show_filter = !empty($settings['show_filter']) && $settings['show_filter'] === 'yes' ? 'yes' : 'no';

        $shortcode = sprintf(
            '[event_list category="%s" columns="%s" per_page="%s" status="%s" orderby="%s" order="%s" show_filter="%s"]',
            esc_attr($category),
            esc_attr($columns),
            esc_attr($settings['per_page'] ?? -1),
            esc_attr($settings['status'] ?? 'all'),
            esc_attr($settings['orderby'] ?? 'date'),
            esc_attr($settings['order'] ?? 'DESC'),
            esc_attr($show_filter)
        );

        echo do_shortcode($shortcode);
    }
}
