<?php
/**
 * WebPopulous Directory Architect - Native Query Filter Engine
 * Drop this safely inside your tsw-custom-listings plugin files.
 */

add_action( 'pre_get_posts', 'webpopulous_native_directory_filter' );

function webpopulous_native_directory_filter( $query ) {
    // Ensure we only modify the front-end, main query loop for our CPT archive page
    if ( is_admin() || ! $query->is_main_query() || ! is_post_type_archive( 'listing' ) ) {
        return;
    }

    // Check if our custom search keyword parameter is set in the URL
    if ( ! empty( $_GET['v_search'] ) ) {
        $query->set( 's', sanitize_text_field( $_GET['v_search'] ) );
    }

    // Check if our custom category dropdown parameter is set in the URL
    if ( ! empty( $_GET['v_cat'] ) ) {
        $query->set( 'tax_query', array(
            array(
                'taxonomy' => 'tsw-taxonomy',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $_GET['v_cat'] ),
            ),
        ) );
    }
}