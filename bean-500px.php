<?php 
/*
Plugin Name: Bean 500px
Plugin URI: http://themebeans.com/plugins/bean-500px
Description: Display photos from 500px that you or your friends have uploaded. Add the Bean 500px widget to a widget area and plug in your username.
Version: 1.1
Author: ThemeBeans
Author URI: http://themebeans.com
*/


// DON'T CALL ANYTHING
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define('BEAN_500PX_PATH', plugin_dir_url( __FILE__ ));


/*===================================================================*/
/*
/* PLUGIN FEATURES SETUP
/*
/*===================================================================*/
$bean_plugin_features[ plugin_basename( __FILE__ ) ] = array(
        "updates"  => false // Whether to utilize plugin updates feature or not
    );


if ( ! function_exists( 'bean_plugin_supports' ) ) 
{
    function bean_plugin_supports( $plugin_basename, $feature ) 
    {
        global $bean_plugin_features;

        $setup = $bean_plugin_features;

        if( isset( $setup[$plugin_basename][$feature] ) && $setup[$plugin_basename][$feature] )
            return true;
        else
            return false;
    }
}


/*===================================================================*/
/*
/* PLUGIN UPDATER FUNCTIONALITY
/*
/*===================================================================*/
define( 'EDD_BEAN500PX_TB_URL', 'http://themebeans.com' );
define( 'EDD_BEAN500PX_NAME', 'Bean 500px' );

if ( bean_plugin_supports ( plugin_basename( __FILE__ ), 'updates' ) ) : // check to see if updates are allowed; only import if so

//LOAD UPDATER CLASS
if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) 
{
    include( dirname( __FILE__ ) . '/updates/EDD_SL_Plugin_Updater.php' );
}
//INCLUDE UPDATER SETUP
include( dirname( __FILE__ ) . '/updates/EDD_SL_Activation.php' );


endif; // END if ( bean_plugin_supports ( plugin_basename( __FILE__ ), 'updates' ) )


/*===================================================================*/
/* UPDATER SETUP
/*===================================================================*/
function bean500px_license_setup() 
{
    add_option( 'edd_bean500px_activate_license', 'BEAN500PX' );
    add_option( 'edd_bean500px_license_status' );
}
add_action( 'init', 'bean500px_license_setup' );

function edd_bean500px_plugin_updater() 
{
    // check to see if updates are allowed; don't do anything if not
    if ( ! bean_plugin_supports ( plugin_basename( __FILE__ ), 'updates' ) ) return;

    //RETRIEVE LICENSE KEY
    $license_key = trim( get_option( 'edd_bean500px_activate_license' ) );

    $edd_updater = new EDD_SL_Plugin_Updater( EDD_BEAN500PX_TB_URL, __FILE__, array( 
            'version' => '1.0',
            'license' => $license_key,
            'item_name' => EDD_BEAN500PX_NAME,
            'author'    => 'ThemeBeans'
        )
    );
}
add_action( 'admin_init', 'edd_bean500px_plugin_updater' );


/*===================================================================*/
/* DEACTIVATION/UNINSTALLATION HOOK - REMOVE OPTION
/*===================================================================*/
function bean500px_deactivate() 
{
    if ( ! current_user_can( 'activate_plugins' ) )
        return;

    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    check_admin_referer( "deactivate-plugin_{$plugin}" );
    
    bean500px_delete_options();
}
function bean500px_uinstall() {
    if ( ! current_user_can( 'activate_plugins' ) )
        return;
    check_admin_referer( 'bulk-plugins' );

    // Important: Check if the file is the one
    // that was registered during the uninstall hook.
    if ( __FILE__ != WP_UNINSTALL_PLUGIN )
        return;

    bean500px_delete_options();
}
function bean500px_delete_options() {
    delete_option( 'edd_bean500px_activate_license' );
    delete_option( 'edd_bean500px_license_status' );

    delete_option( 'bean500px_settings' );
}

register_deactivation_hook( __FILE__, 'bean500px_deactivate' );
register_uninstall_hook( __FILE__, 'bean500px_uinstall' );


// INCLUDE WIDGET
require_once('bean-500px-widget.php');

?>