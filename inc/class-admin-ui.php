<?php
/**
 * Do the thing
 *
 * @package View-Debug-Log
 */

# Busted!
!defined( 'ABSPATH' ) AND exit(
        "<pre>Hi there! I'm just part of a plugin,
            <h1>&iquest;what exactly are you looking for?" );


/**
 * Handles admin interface
 */
class B5F_VDL_Admin_UI {
    /**
     * @var B5F_VDL_Logger Logger instance
     */
    private $logger;

    /**
     * @var B5F_VDL_Settings Settings instance
     */
    private $settings;

    /**
     * @var string Path to "no log" image
     */
    private $no_log_img;

    /**
     * Constructor
     *
     * @param B5F_VDL_Logger $logger Logger instance
     * @param B5F_VDL_Settings $settings Settings instance
     */
    public function __construct(B5F_VDL_Logger $logger, B5F_VDL_Settings $settings) {
        $this->logger = $logger;
        $this->settings = $settings;
        $this->no_log_img = plugins_url('../images/empty.png', __FILE__);
        $this->setup_hooks();
    }

    /**
     * Set up WordPress hooks
     */
    private function setup_hooks() {
        add_action('init', [$this, 'add_admin_menu']);
        
        $hook = is_multisite() ? 'network_' : '';
        add_action("{$hook}admin_menu", [$this, 'make_menu']);
        add_action('vdl_daily_event', [$this->logger, 'daily_maintenance']);

        $action_link = is_multisite() ? 'network_admin_' : '';
        add_filter(
            "{$action_link}plugin_action_links",
            [$this, 'settings_plugin_link'],
            10,
            2
        );

        add_action('wp_ajax_settings_save', [$this, 'settings_save']);

        $option_name = B5F_View_Debug_Log_Plugin::$option_name;
        $showShortcut = get_option("{$option_name}_shortcut", false);
        if ( $showShortcut ) {
            add_action(
                'admin_bar_menu',
                [$this, 'adminBarShortcut'],
                999999
            );
            // Specifically for network admin
            if (is_multisite()) {
                add_action(
                    'network_admin_bar_menu', 
                    [$this, 'adminBarShortcut'], 
                    999999
                );
            }
        }
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        if (is_admin_bar_showing()) {
            add_action(
                'admin_bar_menu',
                [$this, 'add_admin_bar_items'],
                9999
            );
        }
    }

    /**
     * Create plugin menu
     */
    public function make_menu() {
        $hook = is_multisite() ? 'settings.php' : 'tools.php';
        $page = add_submenu_page(
            $hook,
            __('Debug log', 'vdl'),
            __('Debug log', 'vdl'),
            'add_users',
            'debug-log',
            [$this, 'render_debug']
        );
        
        add_action("admin_print_scripts-$page", [$this, 'print_scripts']);
        add_action("admin_head-$page", [$this, 'add_screen_options']);
        add_action('admin_head', [$this, 'css_adminbar_icon']);
    }

    public function css_adminbar_icon() {
        $hook = is_multisite() ? 'settings.php' : 'tools.php';
        ?>
        <style>
            #wp-admin-bar-vdl-menu-bar .igsmb-div::after {
                content: "\f163";
                font-family: dashicons;
                font-size: 16px;
            }
            #wp-admin-bar-vdl-menu-bar .igsmb-div {
                display: flex
            ;
                align-items: center;
                height: inherit;
            }
            #adminmenu a[href='<?php echo $hook; ?>?page=debug-log'] {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 83%;
                padding-right: 10px;
            }

            #adminmenu a[href='<?php echo $hook; ?>?page=debug-log']::after {
                content: "\f163";
                font-family: dashicons;
            }
        </style>
        <?php
    }
    /**
     * Add admin bar items
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function add_admin_bar_items($wp_admin_bar) {
        $wp_admin_bar->add_menu(
            array(
                'parent' => 'network-admin',
                'id'     => 'network-admin-log',
                'title'  => __('Debug log', 'vdl'),
                'href'   => network_admin_url('settings.php?page=debug-log'),
            )
        );
    }

    /**
     * Add admin bar shortcut
     *
     * @param WP_Admin_Bar $admin_bar
     */
    public function adminBarShortcut(\WP_Admin_Bar $admin_bar) {
        if (is_multisite() && !is_super_admin()) {
            return;
        }
        
        // For single site, check regular admin capabilities if needed
        if (!is_multisite() && !current_user_can('manage_options')) {
            return;
        }

        $admin_bar->add_menu(array(
            'id'    => 'vdl-menu-bar',
            'parent' => null,
            'group'  => null,
            'title' => '<div class="igsmb-div" aria-hidden="true" style="overflow:hidden"><span style="text-indent: -9999px">View Debug Log</span></div>',
            'href'  => $this->get_plugin_link('', false),
            'meta' => [
                'title' => 'View Debug Log',
            ]
        ));
    }

    /**
     * Add screen options
     */
    public function add_screen_options() {
        $screen = get_current_screen();
        $screen->add_help_tab(array(
            'id' => 'vdl_help_tab',
            'title' => __('Settings', 'vdl'),
            'content' => file_get_contents(B5FVDL_PATH . 'inc/html/tab-settings.html'),
        ));
        $screen->add_help_tab(array(
            'id' => 'vdl_help2_tab',
            'title' => __('Help', 'vdl'),
            'content' => file_get_contents(B5FVDL_PATH . 'inc/html/tab-help.html'),
        ));
        
        $img = B5FVDL_URL . 'assets/icon.jpg';
        $screen->set_help_sidebar("<p><b>View Debug Log</b><br><img class='icon-help' src='$img' /></p>");
    }

    /**
     * Check posted data and handle actions
     */
    private function check_posted_data() {
        /* this is not stored in the option_ */
        $doReset = false;
        if (!empty($_POST) && check_admin_referer(B5FVDL_BASE)) 
        {
            $option_name = B5F_View_Debug_Log_Plugin::$option_name;
            $option_value = get_option($option_name, []);

            if (isset($_POST[$option_name]['ignore'])) {
                $option_value['ignore'] = true;
            } else {
                unset($option_value['ignore']);
            }
            update_option($option_name, $option_value);

            if (isset($_POST[$option_name]['reset'])) {
                $doReset = true;
                $this->logger->clear_log();
            }
        }
        return $doReset;
    }

    /**
     * Render debug page
     */
    public function render_debug() {
        $doReset = $this->check_posted_data();
        require_once B5FVDL_PATH . '/inc/html/vdl-settings.php';
    }

    /**
     * Enqueue styles and scripts
     */
    public function print_scripts() {
        $option_name = B5F_View_Debug_Log_Plugin::$option_name;

        $vdl_version = filemtime(B5FVDL_PATH . 'assets/vdl.js');
        wp_enqueue_script('vdl', B5FVDL_URL . 'assets/vdl.js', ['jquery'], $vdl_version);
        
        wp_localize_script('vdl', 'wp_ajax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vdl-nonce'),
            'option' => get_option("{$option_name}_shortcut", false),
            'show_settings' => isset($_GET['show-settings']) ? 'yes' : 'no'
        ]);

        $vdls_version = filemtime(B5FVDL_PATH . 'assets/vdl.css');
        wp_enqueue_style('vdl', B5FVDL_URL . 'assets/vdl.css', [], $vdls_version);
    }

    /**
     * Add settings link to plugin action links
     *
     * @param array $links Existing links
     * @param string $file Plugin file
     * @return array Modified links
     */
    public function settings_plugin_link($links, $file) {
        if ($file == B5F_VDL_FILE) {
            $text = __('Settings', 'vdl');
            $links[] = $this->get_plugin_link($text, true, true);
        }
        return $links;
    }

    /**
     * Handle settings save via AJAX
     */
    public function settings_save() {
        check_ajax_referer('vdl-nonce', 'vdl_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to change these settings.', 'vdl')));
        }
        $option_name = B5F_View_Debug_Log_Plugin::$option_name;

        $shortcut_enabled = isset($_POST['shortcut_enabled']) ? (bool)$_POST['shortcut_enabled'] : false;
        update_option("{$option_name}_shortcut", $shortcut_enabled);

        $message = $shortcut_enabled
            ? __('Adminbar shortcut has been enabled.', 'vdl')
            : __('Adminbar shortcut has been disabled.', 'vdl');

        wp_send_json_success(array('message' => $message));
    }

    /**
     * Generates a link to the plugin's admin page
     *
     * @param string $text The link text to display (ignored if $full is false)
     * @param bool $full Whether to return a full HTML link or just the URL
     * @param bool $show Whether to append the 'show-settings' query parameter
     * 
     * @return string Either a complete HTML anchor tag or just the URL
     * 
     * @example 
     * // Returns full link: <a href="...">Settings</a>
     * get_plugin_link('Settings', true, false);
     * 
     * // Returns just URL: "http://example.com/wp-admin/tools.php?page=debug-log"
     * get_plugin_link('', false, false);
     * 
     * // Returns URL with show-settings parameter
     * get_plugin_link('', false, true);
     */
    private function get_plugin_link($text, $full = true, $show = false) {
        $showQuery = $show ? '&show-settings' : '';
        $href = is_multisite()
            ? network_admin_url("settings.php?page=debug-log$showQuery")
            : admin_url("tools.php?page=debug-log$showQuery");
            
        return $full ? sprintf('<a href="%s">%s</a>', $href, $text) : $href;
    }
}
