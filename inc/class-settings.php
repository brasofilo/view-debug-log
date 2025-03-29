<?php
/*
 * Settings API
 *
 * @package View-Debug-Log
 */

# Busted!
!defined( 'ABSPATH' ) AND exit(
        "<pre>Hi there! I'm just part of a plugin,
            <h1>&iquest;what exactly are you looking for?" );


/**
 * Handles plugin settings
 */
class B5F_VDL_Settings {
    /**
     * @var B5F_VDL_Logger Logger instance
     */
    private $logger;

    /**
     * Constructor
     *
     * @param B5F_VDL_Logger $logger Logger instance
     */
    public function __construct(B5F_VDL_Logger $logger) {
        $this->logger = $logger;
        $this->init_settings();
    }

    /**
     * Initialize settings
     */
    private function init_settings() {
        add_action('admin_init', [$this, 'page_init']);
    }

    /**
     * Initialize settings page
     */
    public function page_init() {
        add_settings_section(
            'setting_section_id',
            '',
            null,
            'b5f-vdl-admin'
        );

        $this->add_ignore_field();
        $this->add_reset_field();
        $this->add_size_field();

        register_setting(
            'b5f_vdl_group',
            B5F_View_Debug_Log_Plugin::$option_name
        );
    }

    /**
     * Add reset log field
     */
    private function add_reset_field() {
        add_settings_field(
            'reset',
            '<label for="vdl_option_reset">' . __('Clear log', 'sepw') . '</label>',
            [$this, 'settings_callback'],
            'b5f-vdl-admin',
            'setting_section_id',
            'reset'
        );
    }

    /**
     * Add file size field
     */
    private function add_size_field() {
        add_settings_field(
            'size',
            __('File size', 'sepw'),
            [$this, 'settings_callback'],
            'b5f-vdl-admin',
            'setting_section_id',
            'size'
        );
    }

    /**
     * Add ignore WP_DEBUG field (conditionally)
     */
    private function add_ignore_field() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            add_settings_field(
                'ignore',
                '<label for="vdl_option_ignore">' . __('Ignore WP_DEBUG setting.', 'sepw') . '</label>',
                [$this, 'settings_callback'],
                'b5f-vdl-admin',
                'setting_section_id',
                'ignore'
            );
        }
    }

    /**
     * Settings field callback
     *
     * @param string $args Field type
     */
    public function settings_callback($args) {
        $option_name = B5F_View_Debug_Log_Plugin::$option_name;
        $option_value = get_option($option_name);
        switch ($args) {
            case 'reset':
                printf(
                    '<label><input type="checkbox" name="%s" id="vdl_option_reset" /></label>',
                    $option_name . "[reset]"
                );
                break;
            case 'ignore':
                printf(
                    '<label><input type="checkbox" name="%s" id="vdl_option_ignore" %s /></label>',
                    $option_name . "[ignore]",
                    checked($option_value['ignore'], true, false)
                );
                break;
            case 'size':
                $size = $this->logger->get_log_size();
                printf(
                    '<span style="font-style:italic">%s</span>',
                    $this->logger->format_size($size)
                );
                break;
        }
    }
}

