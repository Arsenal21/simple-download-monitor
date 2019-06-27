<?php

/* 
 * Handles the after login redirect if standard wordpress's user login is being used with this feature.
 */
function sdm_after_wp_user_login_redirect( $redirect_to, $request, $user ) {
    if(isset($_REQUEST['sdm_redirect_to']) && !empty($_REQUEST['sdm_redirect_to'])){
        //Check if the "redirect_user_back_to_download_page" feature is enabled on this site.
        $main_option = get_option( 'sdm_downloads_options' );
        if ( isset( $main_option[ 'redirect_user_back_to_download_page' ] ) ) {
            $redirect_to = urldecode($_REQUEST['sdm_redirect_to']);
        }
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'sdm_after_wp_user_login_redirect', 10, 3 );

/*
 * Handles the redirect in some other situation (example: a plugin is being used for user management/membership system).
 */
function sdm_check_redirect_query_and_settings(){

    if(isset($_REQUEST['sdm_redirect_to']) && !empty($_REQUEST['sdm_redirect_to'])){
        //Check if the "redirect_user_back_to_download_page" feature is enabled on this site.
        $main_option = get_option( 'sdm_downloads_options' );
        if ( isset( $main_option[ 'redirect_user_back_to_download_page' ] ) ) {
            //Check if the user is logged-in (since we only want to redirect a logged-in user.
            $visitor_name = sdm_get_logged_in_user();
            if ($visitor_name !== false ) {
                $redirect_url = urldecode($_REQUEST['sdm_redirect_to']);
                wp_safe_redirect( $redirect_url );//user wp safe redirect.
                exit;
            }
        }
    }    
}