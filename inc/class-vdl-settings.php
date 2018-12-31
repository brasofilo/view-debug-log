<?php
/*
 * Settings API
 *
 * @plugin Favorites Plugin Sorter
 */

# Busted!
!defined( 'ABSPATH' ) AND exit(
        "<pre>Hi there! I'm just part of a plugin,
            <h1>&iquest;what exactly are you looking for?" );


class B5F_VDL_Settings
{
    private $logpath;

	public function __construct()
    {
        $this->logpath = B5F_View_Debug_Log::get_instance()->logpath;

		add_action( 'admin_init', array( $this, 'page_init' ) );
    }


    /**
     * Register and add settings
     */
    public function page_init()
    {
        add_settings_section(
            'setting_section_id',
            '',
            null,
            'b5f-vdl-admin'
        );
        add_settings_field(
            'reset',
            '<label for="vdl_option_reset">'.__( 'Clear log', 'sepw' ).'</label>',
            array( $this, 'settings_callback' ),
            'b5f-vdl-admin',
            'setting_section_id',
            'reset'
        );
        add_settings_field(
            'size',
            __( 'File size', 'sepw' ),
            array( $this, 'settings_callback' ),
            'b5f-vdl-admin',
            'setting_section_id',
            'size'
        );
        $def_debug = defined('WP_DEBUG') && WP_DEBUG;
        if( !$def_debug )
            add_settings_field(
                'ignore',
                '<label for="vdl_option_ignore">'.__( 'Ignore WP_DEBUG setting.', 'sepw' ).'</label>',
                array( $this, 'settings_callback' ),
                'b5f-vdl-admin',
                'setting_section_id',
                'ignore'
            );
        register_setting(
            'b5f_vdl_group',
            B5F_View_Debug_Log::$option_name
        );
    }


    public function settings_callback( $args )
    {
        switch( $args )
        {
            # Get the settings option array and print one of its values
            case 'reset':
                printf(
                    '<label><input type="checkbox" name="%s" id="vdl_option_reset" /></label>',
                    B5F_View_Debug_Log::$option_name."[reset]"
                );
            break;
            # Get the settings option array and print one of its values
            case 'ignore':
                $opt = get_option( B5F_View_Debug_Log::$option_name );
                printf(
                    '<label><input type="checkbox" name="%s" id="vdl_option_ignore" %s /></label>',
                    B5F_View_Debug_Log::$option_name."[ignore]",
                    checked( $opt, 'on', false )
                );
            break;
            # Get the settings option array and print one of its values
            case 'size':
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
            break;
        }
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


}
