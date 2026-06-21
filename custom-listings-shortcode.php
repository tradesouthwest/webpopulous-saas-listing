<?php
/**
 * Shortcode Generator for tsw-custom-listing
 * Converts the View Listings template loop into a portable shortcode [tsw_view_listings limit="5"]
 * @since v1.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function tsw_custom_listings_render_shortcode( $atts ) {
   // 1. Process attributes and support a dynamic post limit override
    $attributes = shortcode_atts( array(
        'limit' => 15, // Default baseline fallback matches your loop template
    ), $atts, 'tsw_view_listings' );

    // 2. Determine the current active page string dynamically
    if ( get_query_var( 'paged' ) ) {
        $paged = get_query_var( 'paged' );
    } elseif ( get_query_var( 'page' ) ) { // Fallback handling for static homepages
        $paged = get_query_var( 'page' );
    } else {
        $paged = 1;
    }

    // 3. Setup production-grade query parameters including the pagination hook
    $args = array(
        'post_type'      => 'listing',
        'posts_per_page' => intval( $attributes['limit'] ),
        'paged'          => $paged,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $the_query = new WP_Query( $args );

    // 4. Initiate output buffering so HTML isn't sent immediately to the screen
    ob_start(); 
    ?>

    <div class="webpop-listing-loop">
        <?php if ( $the_query->have_posts() ) : ?>
            <?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>

                <div class="listing-container">
                    <div class="listing-card">
                        <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

                            <div class="listing-image-wrapper">
                                <figure class="listing-thumb">
                                    <a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>">
                                        <?php if ( ! has_post_thumbnail() ) : ?>
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/default-thumbnail-100x75.png' ); ?>" 
                                                 title="<?php the_title_attribute(); ?>"  
                                                 class="img-responsive list-thumb" 
                                                 alt="" />
                                        <?php else : ?>
                                            <?php the_post_thumbnail(); ?>
                                        <?php endif; ?>
                                    </a>
                                </figure>
                            </div>

                            <div class="listing-content-wrapper">
                                <article class="excerpt-entry">
                                    <header class="listing-card-title">
                                        <h2 class="entry-title">
                                            <a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>">
                                                <?php the_title(); ?>
                                            </a>
                                        </h2>
                                    </header>
                                    
                                    <div class="listing-card-body">
                                        <div class="entry">
                                            <?php the_excerpt(); ?>
                                        </div>
                                        
                                        <div class="metadata">
                                            <p class="cat-link">
                                                <?php 
                                                if ( taxonomy_exists( 'tsw-taxonomy' ) ) {
                                                    echo get_the_term_list( get_the_ID(), 'tsw-taxonomy', '', ', ', '' ); 
                                                } 
                                                ?>
                                                <?php edit_post_link( __( 'Edit', 'larryslist' ) ); ?>
                                            </p>
                                        </div>
                                    </div>
                                </article>
                            </div>

                        </div><!-- ends post id -->
                    </div>
                </div>

            <?php endwhile; ?>

            <!-- 5. Generate Standardized Pagination Output Interface Links -->
            <div class="webpopulous-pagination">
                <?php
                echo paginate_links( array(
                    'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                    'format'       => '?paged=%#%',
                    'current'      => max( 1, $paged ),
                    'total'        => $the_query->max_num_pages,
                    'type'         => 'list', // Renders cleaner, highly stylable UL lists
                    'prev_text'    => __( '&laquo; Prev', 'tsw-custom-listing' ),
                    'next_text'    => __( 'Next &raquo;', 'tsw-custom-listing' ),
                ) );
                ?>
            </div>

            <?php wp_reset_postdata(); // Essential database integrity layer ?>

        <?php else : ?>
            <div class="entry">
                <div class="warning-message">
                    <p><?php esc_html_e( 'No matching vendor profiles found.', 'larryslist' ); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div><!-- ends webpop-listing-loop -->

    <?php
    // 6. Capture layout data block and flush buffer memory
    return ob_get_clean();
}

// 7. Register shortcode string tag dynamically inside WordPress hooks
add_shortcode( 'tsw_view_listings', 'tsw_custom_listings_render_shortcode' );

function webpopulous_short_list_listings_shortcode($atts) {
    // 1. Parse attributes and establish the default limit
    $attributes = shortcode_atts(array(
        'limit' => 5,
    ), $atts);

    // 2. Setup the custom WP_Query for your 'listing' CPT
    $query_args = array(
        'post_type'      => 'listing',
        'posts_per_page' => intval($attributes['limit']),
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $listings_query = new WP_Query($query_args);
    $output = '';

    // 3. The Loop Execution
    if ($listings_query->have_posts()) {
        $output .= '<ul class="webpopulous-short-list">';
        
        while ($listings_query->have_posts()) {
            $listings_query->the_post();
            
            // Fetch relevant data layers for the summary line
            $partner_url = get_post_meta(get_the_ID(), 'webpopulous_partner_url', true);
            $entry_cost  = get_post_meta(get_the_ID(), 'webpopulous_entry_cost', true);
            
            $output .= '<li class="listing-item">';
            $output .= '<a href="' . esc_url(get_permalink()) . '" class="listing-title"><strong>' . esc_html(get_the_title()) . '</strong></a>';
            if ($entry_cost) {
                $output .= ' <span class="listing-cost-badge">(' . esc_html($entry_cost) . ')</span>';
            }
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        
        // Restore global post data integrity
        wp_reset_postdata();
    } else {
        $output .= '<p class="no-listings-found">No vendor listings found.</p>';
    }

    return $output;
}
add_shortcode('short-list-listings', 'webpopulous_short_list_listings_shortcode');

/**
 * Shortcode to render the clean, native search toolbar
 */
add_shortcode( 'webpopulous_search_toolbar', 'webpopulous_render_native_toolbar' );

function webpopulous_render_native_toolbar() {
    $categories = get_terms( array(
        'taxonomy'   => 'tsw-taxonomy',
        'hide_empty' => false,
    ) );

    // Grab current values from the URL to keep form choices persistent on reload
    $current_search = isset( $_GET['v_search'] ) ? sanitize_text_field( $_GET['v_search'] ) : '';
    $current_cat    = isset( $_GET['v_cat'] ) ? sanitize_text_field( $_GET['v_cat'] ) : '';

    ob_start();
    ?>
    <!-- Native GET form submitting right back to the current listing archive page URL -->
    <form method="GET" action="<?php echo esc_url( get_post_type_archive_link( 'listing' ) ); ?>" class="webpopulous-filter-toolbar">
        
        <!-- Search Keyword Input -->
        <input 
            type="text" 
            name="v_search" 
            id="tsw-search-input" 
            placeholder="Search by Vendor..." 
            value="<?php echo esc_attr( $current_search ); ?>" 
        />

        <!-- Category Term Dropdown Selector -->
        <select name="v_cat" id="tsw-category-select" onchange="this.form.submit();">
            <option value="">All Categories</option>
            <?php foreach ( $categories as $cat ) : ?>
                <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $current_cat, $cat->slug ); ?>>
                    <?php echo esc_html( $cat->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Standard Manual Submit Trigger Button -->
        <button type="submit" class="cbutton-primary inline-search-btn" style="display:none;">Filter</button>

        <!-- Escape Portal CTA Link Button -->
        <a href="https://webpopulous.com/find-your-saas/" class="cbutton-primary search-cta">
            Not sure? Find Your SaaS ➜
        </a>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Register shortcode to render the Search Toolbar next to grid
 * @uses `[webpopulous_search_toolbar]`
 */
//add_shortcode( 'webpopulous_search_toolbar', 'webpopulous_render_search_toolbar' );

function webpopulous_render_search_toolbar() {
    // Fetch terms dynamically to fill the selector dropdown void
    $categories = get_terms( array(
        'taxonomy'   => 'tsw-taxonomy',
        'hide_empty' => false,
    ) );

    ob_start();
    ?>
    <div class="webpopulous-filter-toolbar">
        <input type="text" id="tsw-search-input" placeholder="Search by Vendor..." />

        <select id="tsw-category-select">
            <option value=""><?php esc_attr_e( 'All Categories', 'tsw-custom-listing' ); ?></option>
            <?php foreach ( $categories as $cat ) : ?>
                <option value="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
            <?php endforeach; ?>
        </select>

        <p><a href="https://webpopulous.com/find-your-saas/" class="dbutton-primary search-cta">
            <span style="color:#ffffff"><?php esc_attr_e( 'Not sure? Find Your SaaS ➜', 'tsw-custom-listing' ); ?>
        </a></p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('tsw-search-input');
        const catSelect = document.getElementById('tsw-category-select');
        
        function fetchFilteredMatrix() {
            const searchVal = encodeURIComponent(searchInput.value);
            const catVal = encodeURIComponent(catSelect.value);
            
            // Asynchronous Fetch directly targeting the custom plugin endpoint
            fetch(`/wp-json/webpopulous/v1/filter-listings?search=${searchVal}&category=${catVal}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.querySelector('.listing-container');
                    if (!container) return;
                    
                    if (data.length === 0) {
                        container.innerHTML = '<div class="no-listings-found">No specialized SaaS registries found matching that technical profile.</div>';
                        return;
                    }

                    container.innerHTML = data.map(item => {
                        const isMatchClass = item.is_direct_match ? 'is-true-match' : 'is-faded-fill';
                        const matchBadge = item.is_direct_match ? '<span class="match-badge">Exact Match</span>' : '';

                        return `
                            <div class="listing-card ${isMatchClass}">
                                <div class="listing-content-block">
                                    <h3 class="listing-title">
                                        <a href="${item.permalink}">${item.title}</a>
                                        ${matchBadge}
                                    </h3>
                                    <p class="tagline-two">${item.excerpt}</p>
                                    <div class="listing-cost-badge">Entry Cost: ${item.cost}</div>
                                </div>
                            </div>
                        `;
                    }).join('');
                    // Re-render the matrix layout grid without clearing caching states
                  /*  container.innerHTML = data.map(item => `
                        <div class="listing-card">
                            <div class="listing-content-block">
                                <h3 class="listing-title"><a href="${item.permalink}">${item.title}</a></h3>
                                <p class="tagline-two">${item.excerpt}</p>
                                <div class="listing-cost-badge">Entry Cost: ${item.cost}</div>
                            </div>
                        </div>
                    `).join(''); */
                });
        }

        // Debounce text inputs to protect database endpoints from rapid keys
        let delay;
        searchInput.addEventListener('input', () => {
            clearTimeout(delay);
            delay = setTimeout(fetchFilteredMatrix, 300);
        });
        catSelect.addEventListener('change', fetchFilteredMatrix);
    });
    </script>
    <?php
    return ob_get_clean();
}