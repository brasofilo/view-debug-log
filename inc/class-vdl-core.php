<?php
/*
 * Start up
 *
 * Un/Install procedures, language and start all classes
 *
 * @plugin Favorites Plugin Sorter
 */

# Busted!
!defined( 'ABSPATH' ) AND exit(
        "<pre>Hi there! I'm just part of a plugin,
            <h1>&iquest;what exactly are you looking for?" );


class B5F_View_Debug_Log {
	protected static $instance = NULL;
	public static $option_name = 'vdl_option';
	private $options;
    private $reset = false;
	private $no_log_img;
	private $logfile = 'debug.log';
	public $logpath;

	public function __construct() { }

	public static function get_instance() {
		NULL === self::$instance and self::$instance = new self;
		return self::$instance;
	}

	public function plugin_setup() {
		# CLASS PROPERTIES
		$this->no_log_img = plugins_url( '../images/empty.png', __FILE__ );
		$this->logpath = realpath( $_SERVER["DOCUMENT_ROOT"] . '/..' ) . '/' . $this->logfile;
		$options = get_option( self::$option_name );
		if( !$options )
			$options = false;
		$this->options = $options;

		# MENU
		$hook = is_multisite() ? 'network_' : '';
		add_action( "{$hook}admin_menu", array( $this, 'make_menu' ) );

		# CRON JOB, DELETE BIG LOG FILES
		add_action( 'vdl_daily_event', array( $this, 'do_this_daily' ) );

		# SETTINGS LINK
		$action_link = is_multisite() ? 'network_admin_' : '';
		add_filter(
            "{$action_link}plugin_action_links",
            array( $this, 'settings_plugin_link' ),
            10, 2
        );

		# PRIVATE REPO
        include_once __DIR__ . '/plugin-update-dispatch.php';
        $icon = '&hearts;';
        new B5F_General_Updater_and_Plugin_Love(array(
            'repo' => 'view-debug-log',
            'user' => 'brasofilo',
            'plugin_file' => B5F_VDL_FILE,
            'donate_text' => 'Buy me a beer',
            'donate_icon' => "<span  class='fps-icon'>$icon </span>",
            'donate_link' => 'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=JNJXKWBYM9JP6&lc=US&item_name=Rodolfo%20Buaiz&item_number=Plugin%20donation&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted'
        ));

		# SETTINGS API
		global $pagenow;
		if( !in_array( $pagenow, array( 'tools.php', 'settings.php' ) ) )
			return;
		if( !isset( $_GET['page'] ) || 'debug-log' != $_GET['page'] )
			return;
        require_once __DIR__ . '/class-vdl-settings.php';
        new B5F_VDL_Settings();
	}

	public function make_menu() {
		$hook = is_multisite() ? 'settings.php' : 'tools.php';
		$page = add_submenu_page(
			$hook,
			'Debug Log',
			'Debug Log',
			'add_users',
			'debug-log',
			array( $this, 'render_debug' )
		);
		add_action( "admin_print_scripts-$page", array( $this, 'print_style' ) );
	}

    private function check_posted_data() {
		# Security check and clear log
		# the Settings API sanitize is not being called :/
		if (
			!empty( $_POST )
			&& check_admin_referer( plugin_basename( __FILE__ ) )
		) {
            if( isset( $_POST[self::$option_name]['ignore'] ) ) {
                update_option( self::$option_name, 'on' );
                $this->options = 'on';
            }
            else {
                delete_option( self::$option_name );
                $this->options = null;
            }

            if( isset( $_POST[self::$option_name]['reset'] ) ) {
                $this->reset = false;
                if( file_exists( $this->logpath ) ) {
                    $this->reset = true;
        			unlink( $this->logpath );
                }
            }
		}
    }

	public function render_debug() {
		$this->check_posted_data();
        $basename = plugin_basename( __FILE__ ); // used in check_posted_data()
        require_once __DIR__ . '/html-vdl-settings.php';
	}


	public function print_style() {
		echo <<<HTML
<style type="text/css">
	.wrap { margin-left: .5em; width: 80%; }
	#no-cont { margin: 1em 0; font-size: 2em; font-weight:bold; color:#009 }
	#no-cont img {max-width: 330px;}
	code { line-height: 2em; }
	footer { margin-top:2em; opacity: .5 }
</style>
HTML;
	}


	/**
	 * Add link to settings in Plugins list page
	 *
	 * @return Plugin link
	 */
	public function settings_plugin_link( $links, $file ) {
		if( $file == B5F_VDL_FILE ) {
			$text = __( 'Settings', 'sepw' );
			$links[] = $this->get_plugin_link( $text );
		}
		return $links;
	}

	/**
	 * On the scheduled action hook, run a function.
	 */
	function do_this_daily() {
		$megas_10 = 10485760;
		$megas_1_5 = 1572864;
		if ( is_readable( $this->logpath ) ) {
			$size = filesize( $this->logpath );
			if( $size > ($megas_10*5) )
				unlink( $this->logpath );
		}
	}

	private function get_plugin_link( $text ) {
		return sprintf(
				'<a href="%s">%s</a>',
				is_multisite()
					? network_admin_url( 'settings.php?page=debug-log' )
					: admin_url( 'tools.php?page=debug-log' ),
				$text
		);
	}

	/**
	 * On deactivation, remove all functions from the scheduled action hook.
	 */
	public static function deactivation() {
		wp_clear_scheduled_hook( 'vdl_daily_event' );
	}
	public static function activation() {
		wp_schedule_event( time(), 'daily', 'vdl_daily_event');
	}

}
