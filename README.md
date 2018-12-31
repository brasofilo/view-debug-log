View Debug Log
==============

Adds a settings page to view and clear the Debug Log (/wp-content/debug.log)

Use the following to your `wp-config.php` file:

```
define('WP_DEBUG', true);
if ( WP_DEBUG ) {
 
    // turn off wordpress debug (otherwise it will override)
    define( 'WP_DEBUG_LOG', false );
    define('WP_DEBUG_DISPLAY', false);
 
    // specify new safe path
    $path = realpath( $_SERVER["DOCUMENT_ROOT"] . '/..' ) . '/debug.log';
         
    // enable php error log
    @ini_set( 'log_errors', 'On' ); // enable or disable php error logging (use 'On' or 'Off')
    @ini_set( 'error_log', $path );
    error_reporting(0);
    @ini_set('display_errors', 0);
 
}

```

<sup>***Plugin page in Multisite***</sup>  
>![view debug log page](https://raw.github.com/brasofilo/view-debug-log/master/images/screenshot.png)

