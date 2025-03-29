<?php
/**
 * Custom updater
 *
 * @package IG-Scanner
 */

# Busted!
!defined( 'ABSPATH' ) AND exit(
        "<pre>Hi there! I'm just part of a plugin,
            <h1>&iquest;what exactly are you looking for?" );

class B5F_VDL_Updater
{
    /**
     * Transient name
     *
     * @var string
     */
    public $cache_key;

    /**
     * Allow caching
     *
     * @var boolean
     */
    public $cache_allowed = true;

    private $_slug = B5FVDL_SLUG;
    private $_basename = B5FVDL_BASE;
    private $_version = B5FVDL_VER;

    /**
     * Add our custom repository to the update proccess
     * Add plugin row info
     */
    public function __construct() 
    {
        //$this->_slug = B5F_View_Debug_Log::plugin_slug();
        //$this->_basename = B5F_View_Debug_Log::plugin_basename();
        //$this->_version = B5F_View_Debug_Log::plugin_version();
        $slug_limpo = preg_replace('/-/', '_', $this->_slug);
        $this->cache_key = "{$slug_limpo}_updater";

        add_filter( 'plugins_api', [$this, 'info'], 20, 3 );
        add_filter( 'site_transient_update_plugins', [$this, 'update'], 10, 2 );
        add_action( 'upgrader_process_complete', [$this, 'purge'], 10, 2 );
        add_action( 'plugin_row_meta', [$this, 'row_meta'], 10, 3 );
        add_action( 'load-plugins.php', [$this, 'reset_transient'] );
    }

    /**
     * Add 'View details' to our plugin row
     *
     * @param array  $plugin_meta  An array of the plugin’s metadata, including the version, author, author URI, and plugin URI.
     * @param string $_plugin_file Path to the plugin file relative to the plugins directory.
     * @param array  $plugin_data  An array of plugin data.
     * 
     * @return array
     */
    public function row_meta($plugin_meta, $_plugin_file, $plugin_data) 
    {
        $slug = $this->_slug;

        if( $plugin_data['Name'] !== B5FVDL_NAME ) {
            return $plugin_meta;
        }

        if( isset( $plugin_data['update'] ) ) {
            return $plugin_meta;
        }
        $plug_url = admin_url(
            "plugin-install.php?tab=plugin-information&plugin=$slug&TB_iframe=true&"
        );
        if( is_multisite() ) {
            $plug_url = network_admin_url(
                    "plugin-install.php?tab=plugin-information&plugin=$slug&TB_iframe=true&"
                );
        } 
        $plugin_meta[] = sprintf(
            '<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
            esc_url( $plug_url ),
            esc_attr(sprintf(__('More information about %s'), $plugin_data['Name'])),
            esc_attr($plugin_data['Name']),
            __('View details')
        );

        return $plugin_meta;
    }

    /**
     * Call our repository and get the plugin info json
     *
     * @return boolean|object False if error, decoded json on success
     */
    public function request()
    {
        $remote = get_transient( $this->cache_key );

        if( false === $remote || ! $this->cache_allowed ) {
            $slug = $this->_slug;
            $remote = wp_remote_get(
                "https://plugins.brasofilo.com/plg/$slug/sha.json",
                array(
                    'timeout' => 10,
                    'headers' => array(
                        'Accept' => 'application/json'
                    )
                )
            );

            if(
                is_wp_error( $remote )
                || 200 !== wp_remote_retrieve_response_code( $remote )
                || empty( wp_remote_retrieve_body( $remote ) )
            ) {
                return false;
            }
            set_transient( $this->cache_key, $remote, HOUR_IN_SECONDS );
        }
        $remote = json_decode( wp_remote_retrieve_body( $remote ) );
        return $remote;
    }

    /**
     * Filters the response for the current WordPress.org Plugin Installation API request.
     *
     * @param bool|object|array  $res    The result object or array. Default false.
     * @param string             $action The type of information being requested from the Plugin Installation API.
     * @param object             $args   Plugin API arguments.
     * 
     * @return bool|object|array
     */
    function info( $res, $action, $args ) 
    {
        // do nothing if you're not getting plugin information right now
        if( 'plugin_information' !== $action ) {
            return $res;
        }

        // do nothing if it is not our plugin
        if( $this->_slug !== $args->slug ) {
            return $res;
        }

        // get updates
        $remote = $this->request();

        if( ! $remote ) {
            return $res;
        }

        $res = new \stdClass();

        $res->name = $remote->name;
        $res->slug = $remote->slug;
        $res->version = $remote->version;
        $res->tested = $remote->tested;
        $res->requires = $remote->requires;
        $res->author = $remote->author;
        $res->author_profile = $remote->author_profile;
        $res->download_link = $remote->download_url;
        $res->trunk = $remote->download_url;
        $res->requires_php = $remote->requires_php;
        $res->last_updated = $remote->last_updated;

        $res->sections = array(
            'description' => $remote->sections->description,
            'installation' => $remote->sections->installation,
            'changelog' => $remote->sections->changelog
        );

        if( ! empty( $remote->banners ) ) {
            $res->banners = array(
                'low' => $remote->banners->low,
                'high' => $remote->banners->high
            );
        }
        if( ! empty( $remote->icons ) ) {
            $res->icons = array(
                'default' => $remote->icons->default
            );
        }

        return $res;
    }

    /**
     * Run the upgrade check process
     *
     * @param bool|object  $value     Value of site transient.
     * @param string       $transient Transient name.
     * 
     * @return object The transient value
     */
    public function update( $value, $transient ) 
    {
        if ( empty($value->checked ) ) {
            return $value;
        }

        $remote = $this->request();

        if(
            $remote
            && version_compare( $this->_version, $remote->version, '<' )
            && version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' )
            && version_compare( $remote->requires_php, PHP_VERSION, '<' )
        ) {
            $res = new \stdClass();
            $res->slug = $this->_slug;
            $res->plugin = $this->_basename;
            $res->new_version = $remote->version;
            $res->tested = $remote->tested;
            $res->package = $remote->download_url;
            isset( $remote->icons ) && $res->icons = json_decode(json_encode($remote->icons), true);
            
            $value->response[ $res->plugin ] = $res;
        }

        return $value;
    }

    /**
     * Run by upgrader_process_complete
     * remove transient to allow new checks
     *
     * @param object $upgrader WP_Upgrader
     * @param array  $options  Array of bulk item update data
     * 
     * @return void  Will delete transient if true
     */
    public function purge( $upgrader, $options )
    {
        if (
            $this->cache_allowed
            && 'update' === $options['action']
            && 'plugin' === $options[ 'type' ]
        ) {
            // just clean the cache when new plugin version is installed
            delete_transient( $this->cache_key );
        }
    }

    /**
     * Helper to debug the updates, add &reset-cache to /wp-admin/plugins.php?
     *
     * @return void
     */
    public function reset_transient()
    {
        if (
            $this->cache_allowed
            && isset( $_GET['reset-cache'] )
        ) {
            delete_transient( $this->cache_key );
        }
        
    }
}

