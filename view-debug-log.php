<?php

/**
 * Plugin Name: View Debug Log
 * Plugin URI: http://brasofilo.com/manage-debug-log
 * Description: Adds a settings page to view and clear the Debug Log (/wp-content/debug.log)
 * Version: 2018.12.30
 * Author: Rodolfo Buaiz
 * Network: true
 * Author URI:  http://wordpress.stackexchange.com/users/12615/brasofilo
 * License:     GPLv3
 */

/**
 *  License:
 *  ==============================================================================
 *  Copyright Rodolfo Buaiz  License:  (email : rodolfo@rodbuaiz.com)
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	as published by the Free Software Foundation; either version 2
 *	of the License, or (at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
# Busted!
!defined( 'ABSPATH' ) AND exit(
        "<pre>Hi there! I'm just part of a plugin,
            <h1>&iquest;what exactly are you looking for?" );


# Main class
require_once __DIR__ . '/inc/class-vdl-core.php';


# Activate / Deactivate
register_deactivation_hook( __FILE__, array( 'B5F_Manage_Debug_Log', 'deactivation' ) );
register_activation_hook( __FILE__, array( 'B5F_Manage_Debug_Log', 'activation' ) );


# Plugin basename
define( 'B5F_VDL_FILE', plugin_basename( __FILE__ ) );


# STart uP
if( is_admin() )
{
    add_action(
        'plugins_loaded',
        array ( B5F_View_Debug_Log::get_instance(), 'plugin_setup' )
    );
}
