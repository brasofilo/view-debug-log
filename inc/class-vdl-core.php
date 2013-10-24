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


class B5F_View_Debug_Log
{
	protected static $instance = NULL;
	public static $option_name = 'vdl_option';
	private $options;
	private $no_log_img;
	private $logfile = 'debug.log';
	public $logpath;

	public function __construct() { }

	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;
		return self::$instance;
	}

	public function plugin_setup()
	{
		# CLASS PROPERTIES
		$this->no_log_img = plugins_url( '../images/empty.png', __FILE__ );
		$this->logpath = WP_CONTENT_DIR . '/' . $this->logfile;
		$options = get_option( self::$option_name );
		if( !$options )
			$options = array( 'max_size' => '10' );
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
		
		# CUSTOM MESSAGES AFTER PLUGIN UPDATE
        $hooks = array( 
            'update_plugin_complete_actions', 
            'update_bulk_plugins_complete_actions' 
        );
		foreach( $hooks as $h )
			add_filter( $h, array( $this, 'update_msg' ), 10, 2 );

        
		# PRIVATE REPO 
        include_once __DIR__ . '/plugin-update-dispatch.php';
        $icon = '&hearts;';
        new B5F_General_Updater_and_Plugin_Love(array( 
            'repo' => 'view-debug-log', 
            'user' => 'brasofilo',
            'plugin_file' => B5F_FPS_FILE,
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

	public function make_menu()
	{ 
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

	public function render_debug()
	{
		# Security check and clear log
		# the Settings API sanitize is not being called :/
		if ( 
			isset( $_POST[self::$option_name] ) 
			&& wp_verify_nonce( $_POST['b5f_debug_log'], plugin_basename( __FILE__ ) ) 
		)
		{
            if( isset( $_POST[self::$option_name]['ignore'] ) )
                update_option( self::$option_name, 'on' );
            else
                delete_option( self::$option_name );
            
            if( isset( $_POST[self::$option_name]['reset'] ) )
    			unlink( $this->logpath );
		}
		
		# Prepare content
		if ( is_readable( $this->logpath ) ) {
			$handle  = fopen ( $this->logpath, 'r' );
			$content = stream_get_contents( $handle );
			fclose( $handle );
		}
		$content = !empty( $content ) 
            ? '<pre><code>'.print_r($content,true).'</code></pre>' 
            : "<div id='no-cont'><img src='{$this->no_log_img}' style='margin-top:15px' /></div>";
		
		
		# Do it
		?>
<div class="wrap">
	<div id="icon-tools" class="icon32"></div> 
	<h2>Debug Log</h2>
	<div id="poststuff">
	<form action="" method="post" id="notes_form">
	<?php
	wp_nonce_field( plugin_basename( __FILE__ ), 'b5f_debug_log' );
	settings_fields( self::$option_name );   
	do_settings_sections( 'b5f-vdl-admin' );
	submit_button();
	echo "<hr />$content";
	?>
	</form>
	<footer>&hearts; <a href="http://brasofilo.com">Rodolfo Buaiz</a> &middot; <a href="https://github.com/brasofilo/view-debug-log">Github</a></footer>
	</div>
</div>	
	<?php
        if( !defined('WP_DEBUG') || !WP_DEBUG )
            echo "
<div class='error'>Please, use the following in your wp-config.php file:<br />
<pre><code>define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors',0);
</code></pre></div>";
	}

	
	public function print_style()
	{
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
	
	public function update_msg( $actions, $info )
	{
        # The Bulk update $info is an array
        # use the Plugin URI
        $bulk = isset( $info['PluginURI'] ) 
            && 'http://brasofilo.com/manage-debug-log' == $info['PluginURI'];
        
        # Single update $info is a string
        # use the plugin slug
        if( 'view-debug-log/view-debug-log.php' == $info || $bulk )
        {
            $text = __( 'View Debug Log Settings', 'sepw' );
            $link = is_multisite() 
                ? network_admin_url( 'settings.php?page=debug-log' )
                : admin_url( 'tools.php?page=debug-log' );
            $in = "<a href='$link' target='_parent' style='font-weight:bold'>$text</a>";
            array_unshift( $actions, $in );
        }
        
		return $actions;
	}

	/**
	 * Add link to settings in Plugins list page
	 * 
	 * @return Plugin link
	 */
	public function settings_plugin_link( $links, $file )
	{
		if( $file == B5F_VDL_FILE )
		{
			$text = __( 'Settings', 'sepw' );
			$links[] = $this->get_plugin_link( $text );
		}
		return $links;
	}

	/**
	 * On the scheduled action hook, run a function.
	 */
	function do_this_daily() 
	{
		$megas_10 = 10485760;
		$megas_1_5 = 1572864;
		if ( is_readable( $this->logpath ) ) 
		{
			$size = filesize( $this->logpath );
			if( $size > ($megas_10*5) )
				unlink( $this->logpath );
		}
	}
	
	private function get_plugin_link( $text )
	{
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
