<?php
/**
 * This file sets up the auto license activater.
 *
 *
 * @package Bean Plugins
 * @subpackage Bean 500px
 * @author ThemeBeans
 * @since Bean 500px 1.0
 */
 
 
/*===================================================================*/
/* ACTIVATION
/*===================================================================*/
function edd_bean500px_activate_license() 
{
	//GET STATUS
	$status = get_option( 'edd_bean500px_license_status' );
	
	//RETRIEVE LICENSE KEY
	$license = trim( get_option( 'edd_bean500px_activate_license' ) );
	
	//UNCOMMENT TO TEST LICENSE INPUT	
	//echo 'License'; echo $license; echo ' / Status'; echo $status;
	
	//CHECK IF STATUS IS INVALID
	if( $status == '' OR $status == 'invalid' ) 
	{
		//DATA TO SEND WITH OUR API REQUEST
		$api_params = array( 
			'edd_action'=> 'activate_license', 
			'license' 	=> $license, 
			'item_name' => urlencode( EDD_BEAN500PX_NAME )
		);
		
		//CALL CUSTOM API
		$response = wp_remote_get( add_query_arg( $api_params, EDD_BEAN500PX_TB_URL ), array( 'timeout' => 15, 'sslverify' => false ) );
	
		//MAKE SURE RESPONSE IS OK
		if ( is_wp_error( $response ) )
			return false;
	
		//DECODE DATA
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		
		//$license_data->license will be either "active" or "inactive"
	
		update_option( 'edd_bean500px_license_status', $license_data->license );
	}
}
add_action('admin_init', 'edd_bean500px_activate_license');