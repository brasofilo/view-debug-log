View Debug Log
==============

Adds a settings page to view and clear the Debug Log (/wp-content/debug.log)

Use the following to your `wp-config.php` file:

```
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors',0);
```

<sup>***Plugin page in Multisite***</sup>  
>![view debug log page](https://raw.github.com/brasofilo/view-debug-log/master/images/screenshot.png)

