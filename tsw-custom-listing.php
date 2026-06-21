<?php
/*
 * Plugin Name: TSW Custom Listing
 * Plugin URI: http://themes.tradesouthwest.com/wordpress/plugins/TSW-Custom-Listing/
 * Description: Custom post type plugin for posting classifieds like articles to theme LarrysList. Add-ons available at http://themes.tradesouthwest.com
 * Author: Larry Judd Oliver
 * Author URI: http://tradesouthwest.com/
 * Version: 1.1.12
 * Requires at least: 4.5
 * Tested up to:      5.8
 * Requires PHP:      5.4
 * License: GNU General Public License v3.0
 * License URI: http://www.opensource.org/licenses/gpl-license.php
*/

/*  Copyright 2014  Tradesouthwest  (email : larry@tradesouthwest.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * tsw custom profile post type function.
 * 
 * This function creates a new post type for WordPress theme Jacqui and is specific to this theme.
 *
 * @since 1.0.0
 *
 */

// create custom post type for menu Custom Listing

add_action( 'init', 'tsw_listing_create_post_type' );

function tsw_listing_create_post_type() {
register_post_type( 'listing',
    array( 'labels' => array( 
        'name'          => 'Custom Listings',
        'singular_name' => 'Custom Listing',
        'add_new'       => 'Add New',
        'add_new_item'  => 'Add New Listing - Excerpt is first 50 words (about 3 lines)',
        'edit_item'     => 'Edit Listing',
        'new_item'      => 'New Listing',
        'view_item'     => 'View Listing',
        'search_items'  => 'Search Listing',
        'not_found'     => 'No custom listings found',
        'not_found_in_trash' => 'No custom listings found in Trash',
        'parent_item_colon'  => '',
        'menu_name'          => 'Custom Listings'
        ),
        'hierarchical'  => true,
        'description'   => 'Custom post listing will only work on this theme.',
        'supports'      => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
        'taxonomies'    => array( 'post_tag', 'tsw-taxonomy' ),
        'public'        => true,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'show_admin_column' => true,
        'menu_position' => 45,
        'menu_icon'     => plugins_url('tsw-custom-listing/icon_pin24.png'),
        'show_in_nav_menus'   => true,
        'publicly_queryable'  => true,
        'exclude_from_search' => false,
        'has_archive'  => true,
        'map_meta_cap' => true,
	    'hierarchical' => true,
        'rewrite'      => array(
            'slug'       => 'listings', // This changes ?listing=slug to /listings/slug/
            'with_front' => false,      // Prevents adding /blog/ or other prefixes
            'feeds'      => false,
        ),
        'query_var'    => true,        // Allows the engine to parse the query internally
	    'delete_with_user' => true,
        'can_export'       => true,
        'capability_type'  => 'post',
        )
    );

}

function tsw_custom_tax_init(){

  //set some options for our new custom taxonomy
  $args = array(
    'label'        => __( 'Custom Listing Category' ),
    'hierarchical' => true,
    'capabilities' => array(
      // allow anyone editing posts to assign terms
      'assign_terms' => 'edit_posts',
      /* 
      * but you probably don't want anyone except 
      * admins messing with what gets auto-generated! 
      */
      'edit_terms' => 'administrator'
    )
  );

  /* 
  * create the custom taxonomy and attach it to
  * custom post type A 
  */
  register_taxonomy( 'tsw-taxonomy', 'listing', $args);
}

add_action( 'init', 'tsw_custom_tax_init' );

/**
 * 1. Add the Category Column Header
 * Replace 'your_cpt' with your actual Custom Post Type slug.
 */
add_filter( 'manage_listing_posts_columns', 'listing_add_cpt_category_column' );
function listing_add_cpt_category_column( $columns ) {
    // This inserts the column before the date column for better UI flow
    $custom_columns = array();
    foreach ( $columns as $key => $value ) {
        if ( 'date' === $key ) {
            $custom_columns['tsw-taxonomy'] = __( 'Categories', 'tsw-custom-listing' );
        }
        $custom_columns[$key] = $value;
    }
    return $custom_columns;
}

/**
 * 2. Populate the Category Column Rows
 * Custom Post Type slug, 'tsw-taxonomy'
 */
add_action( 'manage_listing_posts_custom_column', 'listing_populate_cpt_category_column', 10, 2 );
function listing_populate_cpt_category_column( $column, $post_id ) {
    if ( 'tsw-taxonomy' === $column ) {
        // Change 'category' to your custom taxonomy slug if not using native categories
        $terms = get_the_terms( $post_id, 'tsw-taxonomy' ); 

        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            $links = array();
            foreach ( $terms as $term ) {
                // Generates a clickable link to filter admin items by this term
                $links[] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( admin_url( 'edit.php?post_type=listing&tsw-taxonomy=' . $term->slug ) ),
                    esc_html( $term->name )
                );
            }
            echo implode( ', ', $links );
        } else {
            echo '<em>' . __( 'No Categories', 'tsw-custom-listing' ) . '</em>';
        }
    }
}

/**
 * Limit caps on user posts
 * @since 1.0
 */
function listing_map_meta_cap($caps, $cap, $user_id, $args)
{

    if ( 'edit_listing' == $cap || 'delete_listing' == $cap || 'read_listing' == $cap ) {
        $post = get_post( 'listing' );
        $post_type = get_post_type_object( $post->post_type );
        $caps = array();
    }

    if ( 'edit_listing' == $cap ) {
        if ( $user_id == $post->post_author )
            $caps[] = $post_type->cap->edit_post;
        else
            $caps[] = $post_type->cap->edit_others_post;
    }

    elseif ( 'delete_listing' == $cap ) {
        if ( $user_id == $post->post_author )
            $caps[] = $post_type->cap->delete_post;
        else
            $caps[] = $post_type->cap->delete_others_post;
    }

    elseif ( 'read_listing' == $cap ) {
        if ( 'private' != $post->post_status )
            $caps[] = 'read';
        elseif ( $user_id == $post->post_author )
            $caps[] = 'read';
        else
            $caps[] = $post_type->cap->read_private_posts;
    }

    return $caps;
}
add_filter( 'map_meta_cap', 'listing_map_meta_cap', 10, 4 );

/**
 * User Manages their Media Only
 * @WP_User
 */
add_action('pre_get_posts','tsw_users_own_attachments');
function tsw_users_own_attachments( $wp_query_obj ) {

    global $current_user, $pagenow;

    if( !is_a( $current_user, 'WP_User->ID') )
        return;

    if( (   'edit.php' != $pagenow ) &&
    (   'upload.php' != $pagenow ) &&
    ( ( 'admin-ajax.php' != $pagenow ) || ( $_REQUEST['action'] != 'query-attachments' ) ) )
    return;

    if( !current_user_can('delete_pages') )
        $wp_query_obj->set('author', $current_user->id );

    return;
}
/**
 * sets only user posts as available to edit in admin
 */
function tsw_posts_for_current_author($query) {
	global $user_level;

	if($query->is_admin && $user_level < 5) {
		global $user_ID;
		$query->set('author',  $user_ID);
		unset($user_ID);
	}
	unset($user_level);

	return $query;
}
add_filter('pre_get_posts', 'tsw_posts_for_current_author');

/**
 * @since 2.0 adding styles to head
 */
function tsw_custom_listing_styles_enqueue(){
    /*
     * Register Styles */
    wp_register_style( 'tsw-listing-style', 
                    plugin_dir_url(__FILE__) . 'style-listing.css'
                    );
    // The plugin stylesheet 
    wp_enqueue_style( 'tsw-listing-style', 
                     plugin_dir_url(__FILE__) . 'style-listing.css',
                        array(), time(), 
                        false 
                    );
}
add_action( 'wp_enqueue_scripts',  'tsw_custom_listing_styles_enqueue' );

/**
 * custom color 
 */
function tsw_custom_custom_colors() {
    if ( !current_user_can('update_plugins')) {
        echo '<style type="text/css">
           #adminmenuback, #adminmenuwrap{background:#d8d8d8}
           #adminmenu li{background:#a94242}
           #dashboard_right_now{display:none}
           span.ab-icon {display:none}
           #footer-upgrade{display:none}
           #wp-version-message{display:none}
           .inside a:first-child{font-size:19px}
         </style>';
    }
}

add_action('admin_head', 'tsw_custom_custom_colors');

// custom admin login logo
function tsw_custom_login_logo() {
if ( !current_user_can('update_plugins')) {
$url = plugins_url('tsw-custom-listing/custom-login-logo.png');
	echo '<style type="text/css">
	h1 a { background-image: url($url) }
	</style>';
  }
}
add_action('login_head', 'tsw_custom_login_logo');

/**
 * remove admin widgets from normal users dashboard
 * add custom widgets to display user's posts
 */
add_action('wp_dashboard_setup', 'tsw_custom_dashboard_widgets');

function tsw_custom_dashboard_widgets() {
if ( !current_user_can('update_plugins')) {
    global $wp_meta_boxes;
        unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
        unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
        unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
        unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
        unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
        unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_right_now']);
}
    wp_add_dashboard_widget('custom_help_widget', 'Currently Listed', 'tsw_custom_dashboard_help');
}

function tsw_custom_dashboard_help() {
    global $current_user;
    $current_user = wp_get_current_user();
            $author_query = array(
            'author' => $current_user->ID,
            'post_type'=>'listing'
            );
    $author_posts = new WP_Query($author_query); ?>
    <h3> <?php echo $current_user->user_login ?> </h3>
    <?php
    if ( $author_posts->have_posts() ) : 
        while ( $author_posts->have_posts() ) : 
            $author_posts->the_post(); ?>
    <li><a href="<?php the_permalink() ?>" rel="bookmark" 
            title=" <?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>                       
    <?php endwhile; ?>
    <?php endif; 
    wp_reset_postdata(); 
}

/**
 * custom widget to display custom post stats on dashboard
 */ 
    // wp_dashboard_setup is the action hook
add_action('wp_dashboard_setup', 'tsw_custom_dashboard_stats');

// add dashboard widget
function tsw_custom_dashboard_stats() {
    wp_add_dashboard_widget('listing_stat_widget', 'Listing Stats','tsw_dashboard_custom_post_types');
}
function tsw_dashboard_custom_post_types() {
 $authorid = get_current_user_id();
        query_posts(array( 
            'post_type' => 'listing',
            'author'    => $authorid,
        ) ); 
            $count = 0;
            while (have_posts()) : the_post(); 
                $count++; 
            endwhile;
            echo '<h3>' . $count ;
            echo ' Listings</h3>';
        wp_reset_query();
 
} 

/**
 * WebPopulous Core Metadata Fields Engine
 * Public Meta Key Version (Visible in native Custom Fields panel)
 */

// 1. Register the Meta Box
function webpopulous_add_listing_meta_box() {
    add_meta_box(
        'webpopulous_listing_specs',
        'WebPopulous Matrix Technical Specs',
        'webpopulous_render_listing_meta_box',
        'listing',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes_listing', 'webpopulous_add_listing_meta_box' );
add_action( 'add_meta_boxes', 'webpopulous_add_listing_meta_box' );

// 2. Render the Backend Fields View
function webpopulous_render_listing_meta_box( $post ) {
    wp_nonce_field( 'webpopulous_save_listing_meta', 'webpopulous_listing_nonce' );

    // Stripped underscores so keys match public post_meta names
    $true_whitelabel = get_post_meta( $post->ID, 'webpopulous_true_whitelabel', true );
    $sub_billing     = get_post_meta( $post->ID, 'webpopulous_sub_billing', true );
    $payout_margin   = get_post_meta( $post->ID, 'webpopulous_payout_margin', true );
    $entry_cost      = get_post_meta( $post->ID, 'webpopulous_entry_cost', true );
    $multi_tenant    = get_post_meta( $post->ID, 'webpopulous_multi_tenant', true );
    $target_industry = get_post_meta( $post->ID, 'webpopulous_target_industry', true );
    $partner_url     = get_post_meta( $post->ID, 'webpopulous_partner_url', true );
    
    ?>
    <style>
        .wp-matrix-meta-row { display: flex; margin-bottom: 15px; align-items: center; }
        .wp-matrix-meta-row label { width: 220px; font-weight: bold; padding-right: 10px; }
        .wp-matrix-meta-row input[type="text"], .wp-matrix-meta-row select { width: 100%; max-width: 450px; padding: 6px; }
    </style>

    <div class="wp-matrix-meta-wrapper">
        
        <div class="wp-matrix-meta-row">
            <label for="wp_true_whitelabel">True Domain Masking:</label>
            <select id="wp_true_whitelabel" name="wp_true_whitelabel">
                <option value="" <?php selected( $true_whitelabel, '' ); ?>>-- Select Option --</option>
                <option value="Yes" <?php selected( $true_whitelabel, 'Yes' ); ?>>Yes (Full Masking/No Vendor Footprints)</option>
                <option value="No" <?php selected( $true_whitelabel, 'No' ); ?>>No (Co-Branded/Logo Presence Only)</option>
            </select>
        </div>

        <div class="wp-matrix-meta-row">
            <label for="wp_sub_billing">Client Sub-Billing:</label>
            <select id="wp_sub_billing" name="wp_sub_billing">
                <option value="" <?php selected( $sub_billing, '' ); ?>>-- Select Option --</option>
                <option value="Yes" <?php selected( $sub_billing, 'Yes' ); ?>>Yes (Direct Stripe Connect / Whitelabel Invoicing)</option>
                <option value="No" <?php selected( $sub_billing, 'No' ); ?>>No (Clients must pay vendor directly)</option>
            </select>
        </div>

        <div class="wp-matrix-meta-row">
            <label for="wp_payout_margin">Reseller / Payout Margin:</label>
            <input type="text" id="wp_payout_margin" name="wp_payout_margin" value="<?php echo esc_attr( $payout_margin ); ?>" placeholder="e.g., 30% Lifetime MRR" />
        </div>

        <div class="wp-matrix-meta-row">
            <label for="wp_entry_cost">Entry Tier Cost:</label>
            <input type="text" id="wp_entry_cost" name="wp_entry_cost" value="<?php echo esc_attr( $entry_cost ); ?>" placeholder="e.g., $149/mo - Growth Tier" />
        </div>

        <div class="wp-matrix-meta-row">
            <label for="wp_multi_tenant">Multi-Tenant Dashboard:</label>
            <select id="wp_multi_tenant" name="wp_multi_tenant">
                <option value="" <?php selected( $multi_tenant, '' ); ?>>-- Select Option --</option>
                <option value="Yes" <?php selected( $multi_tenant, 'Yes' ); ?>>Yes (Switch accounts easily from single log-in)</option>
                <option value="No" <?php selected( $multi_tenant, 'No' ); ?>>No (Requires unique email log-ins per client)</option>
            </select>
        </div>

        <div class="wp-matrix-meta-row">
            <label for="wp_target_industry">Target Industry Focus:</label>
            <input type="text" id="wp_target_industry" name="wp_target_industry" value="<?php echo esc_attr( $target_industry ); ?>" placeholder="e.g., Retail, Medical, Real Estate" />
        </div>

        <div class="wp-matrix-meta-row">
            <label for="wp_partner_url">Partner Portal Link:</label>
            <input type="text" id="wp_partner_url" name="wp_partner_url" value="<?php echo esc_url( $partner_url ); ?>" placeholder="https://example.com/partners" />
        </div>

    </div>
    <?php
}

// 3. Save & Sanitize the Meta Data
function webpopulous_save_listing_meta( $post_id, $post ) {
    if ( ! isset( $_POST['webpopulous_listing_nonce'] ) || ! wp_verify_nonce( $_POST['webpopulous_listing_nonce'], 'webpopulous_save_listing_meta' ) ) {
        return $post_id;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $post_id;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return $post_id;
    }

    if ( isset( $_POST['post_type'] ) && $_POST['post_type'] !== 'listing' ) {
        return $post_id;
    } elseif ( $post->post_type !== 'listing' ) {
        return $post_id;
    }

    // Stripped underscores from keys here as well to write public records
    if ( isset( $_POST['wp_true_whitelabel'] ) ) {
        update_post_meta( $post_id, 'webpopulous_true_whitelabel', sanitize_text_field( $_POST['wp_true_whitelabel'] ) );
    }
    if ( isset( $_POST['wp_sub_billing'] ) ) {
        update_post_meta( $post_id, 'webpopulous_sub_billing', sanitize_text_field( $_POST['wp_sub_billing'] ) );
    }
    if ( isset( $_POST['wp_payout_margin'] ) ) {
        update_post_meta( $post_id, 'webpopulous_payout_margin', sanitize_text_field( $_POST['wp_payout_margin'] ) );
    }
    if ( isset( $_POST['wp_entry_cost'] ) ) {
        update_post_meta( $post_id, 'webpopulous_entry_cost', sanitize_text_field( $_POST['wp_entry_cost'] ) );
    }
    if ( isset( $_POST['wp_multi_tenant'] ) ) {
        update_post_meta( $post_id, 'webpopulous_multi_tenant', sanitize_text_field( $_POST['wp_multi_tenant'] ) );
    }
    if ( isset( $_POST['wp_target_industry'] ) ) {
        update_post_meta( $post_id, 'webpopulous_target_industry', sanitize_text_field( $_POST['wp_target_industry'] ) );
    }
    if ( isset( $_POST['wp_partner_url'] ) ) {
        update_post_meta( $post_id, 'webpopulous_partner_url', esc_url_raw( $_POST['wp_partner_url'] ) );
    }
}
add_action( 'save_post', 'webpopulous_save_listing_meta', 10, 2 );

require_once plugin_dir_path( __FILE__ ) . 'tsw-custom-listing-search-query.php';
require_once plugin_dir_path( __FILE__ ) . 'tsw-listing-categories-widget.php';
require_once plugin_dir_path( __FILE__ ) . 'custom-listings-shortcode.php';
?>
