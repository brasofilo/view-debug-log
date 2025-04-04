<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
# Prepare content
if ( is_readable( $this->logger->logpath ) ) {
    $handle  = fopen ( $this->logger->logpath, 'r' );
    $content = stream_get_contents( $handle );
    fclose( $handle );
}
$pre_content = '<div class="log-container"><div class="log-content"><pre><code>';
$post_content = '</code></pre></div></div>';
$content = !empty( $content )
    ? $pre_content.print_r($content,true).$post_content
    : "<div id='no-cont'><img src='{$this->no_log_img}' style='margin-top:15px' /></div>";

# Do it
?>
<div class="wrap">
	<div id="icon-tools" class="icon32"></div>
	<h2>Debug Log <?php echo B5FVDL_VER; ?></h2>
	<div id="poststuff">
            <form method="post" action="">
            <?php
                //settings_fields( 'b5f_vdl_group' );
                wp_nonce_field( B5FVDL_BASE );
                do_settings_sections( 'b5f-vdl-admin' );
                submit_button( 'Update' );
            ?>
            </form>
        <?php
	echo "<hr />$content";
	?>
	<footer>&hearts; <a href="http://brasofilo.com">Rodolfo Buaiz</a> &middot; <a href="https://github.com/brasofilo/view-debug-log">Github</a></footer>
	</div>
</div>
<?php
    # SHOW WARNING
    $def_debug = defined('WP_DEBUG') && WP_DEBUG;
    $def_log = defined('WP_DEBUG_LOG') && !WP_DEBUG_LOG;
    $option_name = B5F_View_Debug_Log_Plugin::$option_name;
    $option_value = get_option($option_name);

    if( !$def_debug && empty($option_value['ignore']) ) {
    ?>
        <div class='error' style="padding:10px 0 0 20px">
            <strong>Please, use the following in your wp-config.php file:</strong>

            <pre>
            define('WP_DEBUG', true);
            if ( WP_DEBUG ) {
                define( 'WP_DEBUG_LOG', false );
                define('WP_DEBUG_DISPLAY', false);
                $path = realpath( $_SERVER["DOCUMENT_ROOT"] . '/..' ) . '/debug.log';
                @ini_set( 'log_errors', 'On' ); // enable or disable php error logging (use 'On' or 'Off')
                @ini_set( 'error_log', $path );
                error_reporting(0);
                @ini_set('display_errors', 0);
            }</pre>
        </div>
    <?php
    } # END WARNING

    # FILE CLEARED
    if( $doReset )
    {
        ?>
        <div style="width:99%; padding: 5px;" class="updated">
            <p>Cleared.</p>
        </div>
        <?php
    }
