<?php

/**
 * Plugin Name: View Debug Log
 * Plugin URI: http://brasofilo.com/manage-debug-log
 * Description: Adds a settings page to view and clear the Debug Log (/wp-content/debug.log)
 * Version: 2013.09.19
 * Author: Rodolfo Buaiz
 * Network: true
 * Author URI: http://wordpress.stackexchange.com/users/12615/brasofilo
 * Licence: GPLv2 or later
 */

!defined( 'ABSPATH' ) AND exit(
	"<pre>Hi there! I'm just part of a plugin, <h1>&iquest;what exactly are you looking for?"
);

register_deactivation_hook( __FILE__, array( 'B5F_Manage_Debug_Log', 'deactivation' ) );
register_activation_hook( __FILE__, array( 'B5F_Manage_Debug_Log', 'activation' ) );


add_action(
	'plugins_loaded', 
	array( B5F_Manage_Debug_Log::get_instance(), 'plugin_setup' )
); 


class B5F_Manage_Debug_Log
{
	protected static $instance = NULL;
	public static $option_name = 'vdl_option';
	private $options;
	private $no_log_img;
	private $logfile = 'debug.log';
	private $logpath;

	public function __construct() { }

	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;
		return self::$instance;
	}

	public function plugin_setup()
	{
		# CLASS PROPERTIES
		$this->no_log_img = plugins_url( '/images/empty.png', __FILE__ );
		$this->logpath = WP_CONTENT_DIR . '/' . $this->logfile;
		$options = get_option( self::$option_name );
		if( !$options )
			$options = array( 'max_size' => '10' );
		$this->options = $options;
		
		# MENU
		$hook = is_multisite() ? 'network_' : '';
		add_action( "{$hook}admin_menu", array( $this, 'make_menu' ) );
		
		# SETTINGS LINK
		$action_link = is_multisite() ? 'network_admin_' : '';
		add_filter( "{$action_link}plugin_action_links", array( $this, 'settings_plugin_link' ), 10, 2 );
		
		# RENAME GITHUB MASTER DIRECTORY
		add_filter( 'upgrader_source_selection', array( $this, 'rename_github_zip' ), 1, 3);

		# CRON JOB, DELETE BIG LOG FILES
		add_action( 'vdl_daily_event', array( $this, 'do_this_daily' ) );
		
		# CUSTOM MESSAGES AFTER PLUGIN UPDATE
		foreach( array( 'update_plugin_complete_actions', 'update_bulk_plugins_complete_actions' ) as $msg_hook )
			add_filter( $msg_hook, array( $this, 'bulk_update_msg' ), 10, 2 );

		# PRIVATE REPO 
		include_once 'includes/plugin-updates/plugin-update-checker.php';
		$updateChecker = new PluginUpdateChecker(
			'https://raw.github.com/brasofilo/view-debug-log/master/includes/update.json',
			__FILE__,
			'view-debug-log-master'
		);
		
		# SETTINGS API
		global $pagenow;
		if( !in_array( $pagenow, array( 'tools.php', 'settings.php' ) ) )
			return;
		if( !isset( $_GET['page'] ) || 'debug-log' != $_GET['page'] )
			return;
		add_action( 'admin_init', array( $this, 'page_init' ) );
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
			&& isset( $_POST[self::$option_name]['reset'] ) 
			&& wp_verify_nonce( $_POST['b5f_debug_log'], plugin_basename( __FILE__ ) ) 
		)
		{
			unlink( $this->logpath );
		}
		
		# Prepare content
		if ( is_readable( $this->logpath ) ) {
			$handle  = fopen ( $this->logpath, 'r' );
			$content = stream_get_contents( $handle );
			fclose( $handle );
		}
		$content = !empty( $content ) ? '<pre><code>'.print_r($content,true).'</code></pre>' : "<div id='no-cont'><img src='{$this->no_log_img}' style='margin-top:15px' /></div>";
		
		
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
	}

	    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            self::$option_name, // Option group
            self::$option_name, // Option name
            null //array( $this, 'sanitize_debug_log' ) // Sanitize
        );
        add_settings_section(
            'setting_section_id', // ID
            '', // Title
            null, // Callback
            'b5f-vdl-admin' // Page
        );  
        /*add_settings_field(
            'max_size', // ID
            __( 'Maximum file size', 'sepw' ), // Title 
            array( $this, 'max_size_callback' ), // Callback
            'b5f-vdl-admin', // Page
            'setting_section_id' // Section           
        ); */   
        add_settings_field(
            'reset', 
            '<label for="vdl_option_reset">'.__( 'Clear log', 'sepw' ).'</label>', 
            array( $this, 'reset_callback' ), 
            'b5f-vdl-admin', 
            'setting_section_id'
        );      
        add_settings_field(
            'size', 
            __( 'File size', 'sepw' ), 
            array( $this, 'size_callback' ), 
            'b5f-vdl-admin', 
            'setting_section_id'
        );      
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function max_size_callback()
    {
		printf(
			'<label><input type="text" name="%s" value="%s" /> %s</label>',
			self::$option_name.'[max_size]',
			esc_attr( $this->options['max_size'] ),
			''
		);
   }

    /** 
     * Get the settings option array and print one of its values
     */
    public function reset_callback()
    {
		printf(
			'<label><input type="checkbox" name="%s" id="vdl_option_reset" /> %s</label>',
			self::$option_name."[reset]",
			''
		);
    }
	
    /** 
     * Get the settings option array and print one of its values
     */
    public function size_callback()
    {
		$size = 0;
		# Read log
		if ( is_readable( $this->logpath ) ) {
			$size = filesize( $this->logpath );
			$handle  = fopen ( $this->logpath, 'r' );
			$content = stream_get_contents( $handle );
			fclose( $handle );
		}
		$megas_10 = 10485760;
		$megas_1_5 = 1572864;
		printf(
				'<span style="font-style:italic">%s</span>',
				$this->format_size( $size )
		);
    }
	
	
    /**
     * Sanitize each setting field as needed
	 * Empty cache, delete transient
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize_debug_log( $input )
    {
		if( !empty( $input['reset'] ) ) 
			unlink( $this->logpath );
        return $input;
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
	
	public function bulk_update_msg( $actions, $info )
	{
		$text = __( 'View Debug Log Settings', 'sepw' );
		$link = is_multisite() 
			? network_admin_url( 'settings.php?page=debug-log' )
			: admin_url( 'tools.php?page=debug-log' );
		$actions['debug_log'] = "<a href='$link' target='_parent'>$text</a>";
		return $actions;
	}

	/**
	 * Add link to settings in Plugins list page
	 * 
	 * @return Plugin link
	 */
	public function settings_plugin_link( $links, $file )
	{
		$base = plugin_basename( __FILE__ );
		if( $file == $base )
		{
			$text = __( 'Settings', 'sepw' );
			$in = $this->get_plugin_link( $text );
			array_unshift( $links, $in );
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
	
	/**
	 * Removes the prefix "-master" when updating from GitHub zip files
	 * 
	 * See: https://github.com/YahnisElsts/plugin-update-checker/issues/1
	 * 
	 * @param string $source
	 * @param string $remote_source
	 * @param object $thiz
	 * @return string
	 */
	public function rename_github_zip( $source, $remote_source, $thiz )
	{
		if(  strpos( $source, 'view-debug-log') === false )
			return $source;

		$path_parts = pathinfo($source);
		$newsource = trailingslashit($path_parts['dirname']). trailingslashit('view-debug-log');
		rename($source, $newsource);
		return $newsource;
	}

	
	# http://stackoverflow.com/a/8348396/1287812
	private function format_size($size) {
		$units = explode(' ', 'B KB MB GB TB PB');
		$mod = 1024;
		for ($i = 0; $size > $mod; $i++) {
			$size /= $mod;
		}
		$endIndex = strpos($size, ".")+3;
		return substr( $size, 0, $endIndex).' '.$units[$i];
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
