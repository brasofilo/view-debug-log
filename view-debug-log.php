<?php
/**
 * Plugin Name: View Debug Log
 * Plugin URI: http://brasofilo.com/manage-debug-log
 * Description: Adds a settings page to view and clear the Debug Log (/wp-content/debug.log)
 * Version: 3.1.1
 * Author: Rodolfo Buaiz
 * Author URI:  http://wordpress.stackexchange.com/users/12615/brasofilo
 * Network: true
 * Text Domain: vdl
 * Domain Path: languages/
 * License:     GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package View-Debug-Log
 */


/**
 *  License:
 *  ==============================================================================
 *  Copyright Rodolfo Buaiz  License:  (email : rodolfo@rodbuaiz.com)
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; either version 2
 *  of the License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
# Busted!
!defined('ABSPATH') and exit(
    "<pre>Hi there! I'm just part of a plugin,
            <h1>&iquest;what exactly are you looking for?"
);


$path = plugin_dir_path( __FILE__ ) . 'inc/';
foreach (glob($path . '*.php') as $file) {
    require_once $file;
}

/**
 * Main plugin class that initializes and coordinates all components
 */
class B5F_View_Debug_Log_Plugin {
    /**
     * @var B5F_View_Debug_Log_Plugin|null Singleton instance
     */
    protected static $instance = null;

    /**
     * @var string Plugin option name
     */
    public static $option_name = 'vdl_option';

    /**
     * @var B5F_VDL_Logger Handles log file operations
     */
    private $logger;

    /**
     * @var B5F_VDL_Admin_UI Handles admin interface
     */
    private $admin_ui;

    /**
     * @var B5F_VDL_Settings Handles plugin settings
     */
    private $settings;

    /**
     * Get singleton instance
     *
     * @return B5F_View_Debug_Log_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
            self::$instance->define_constants();
            self::$instance->initialize_components();
            self::$instance->setup_hooks();
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin
     */
    public function plugin_setup() {
        #$this->define_constants();
        #$this->initialize_components();
        #$this->setup_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        $plugin_data = get_file_data(
            __FILE__,
            array(
                'Version'     => 'Version',
                'Plugin Name' => 'Plugin Name',
                'Author'      => 'Author',
                'Description' => 'Description',
                'Plugin URI'  => 'Plugin URI',
            ),
            false
        );
        if ( defined('B5FVDL_SLUG') ) return;
        define('B5FVDL_SLUG', dirname(plugin_basename(__FILE__)));
        define('B5FVDL_BASE', plugin_basename(__FILE__));
        define('B5FVDL_PATH', trailingslashit(plugin_dir_path(__FILE__)));
        define('B5FVDL_URL', trailingslashit(plugins_url('/', __FILE__)));
        define('B5FVDL_NAME', $plugin_data['Plugin Name']);
        define('B5FVDL_VER', $plugin_data['Version']);
        define('B5F_VDL_FILE', plugin_basename(__FILE__));
    }

    /**
     * Initialize plugin components
     */
    private function initialize_components() {
        $this->logger = new B5F_VDL_Logger();
        $this->settings = new B5F_VDL_Settings($this->logger);
        $this->admin_ui = new B5F_VDL_Admin_UI($this->logger, $this->settings);
        new B5F_VDL_Updater();
    }

    /**
     * Set up WordPress hooks
     */
    private function setup_hooks() {
        if (is_admin()) {
            add_action('plugins_loaded', [$this, 'plugin_setup']);
        }

        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivation']);
        register_activation_hook(__FILE__, [__CLASS__, 'activation']);
    }

    /**
     * Plugin activation handler
     */
    public static function activation() {
        wp_schedule_event(time(), 'daily', 'vdl_daily_event');
    }

    /**
     * Plugin deactivation handler
     */
    public static function deactivation() {
        wp_clear_scheduled_hook('vdl_daily_event');
    }
}

// Initialize the plugin
if (is_admin()) {
    B5F_View_Debug_Log_Plugin::get_instance()->plugin_setup();
}