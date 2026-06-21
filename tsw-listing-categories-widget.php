<?php
/**
 * TSW-Custom-Listing - Custom Widget Extensions
 * Description: Standard-compliant widget additions built natively for the WebPopulous matrix.
 * Version: 1.0.0
 * Author: WebPopulous Directory Architect
 */

// Exit if accessed directly to maintain environmental isolation
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * TSW_Listing_Categories_Widget Class
 * * Extends the native WP_Widget configuration to query and display
 * taxonomies belonging exclusively to the 'tsw-taxonomy' matrix.
 */
class TSW_Listing_Categories_Widget extends WP_Widget {

    /**
     * 1. Constructor Engine
     * Sets unique base ID, localized control name, and dashboard meta parameters.
     */
    public function __construct() {
        parent::__construct(
            'tsw_listing_categories_widget', // Base structural HTML control ID
            esc_html__( 'WebPopulous Listing Categories', 'tsw-custom-listing' ), // Human-readable label
            array( 
                'description'                 => esc_html__( 'Renders active terms belonging to \'tsw-taxonomy\'.', 'tsw-custom-listing' ),
                'customize_selective_refresh' => true, // Enforce compatibility with the Customizer preview matrix
            )
        );
    }

    /**
     * 2. Frontend Render Engine
     * Outflow loop targeting 'tsw-taxonomy' terms cleanly wrapped in system markup components.
     */
    public function widget( $args, $instance ) {
        // Parse current state values and establish system fallback thresholds
        $title  = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;

        // Output theme configuration layout open-wrappers
        echo $args['before_widget'];

        // Render Title Block if assigned
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        // Query taxonomy terms linked explicitly to the 'listing' Custom Post Type matrix
        $terms_args = array(
            'taxonomy'   => 'tsw-taxonomy',
            'number'     => $number,
            'hide_empty' => false, // Set to true if you want to automatically suppress empty placeholders
        );

        $terms = get_terms( $terms_args );

        // Evaluate extraction array bounds safely before driving markup outputs
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            echo '<ul class="webpopulous-category-sidebar-list">';
            
            foreach ( $terms as $term ) {
                // Ensure WP_Term links are built safely before printing
                $term_link = get_term_link( $term );
                if ( is_wp_error( $term_link ) ) {
                    continue;
                }

                echo '<li class="category-item">';
                echo '<a href="' . esc_url( $term_link ) . '" class="category-link">';
                echo esc_html( $term->name );
                echo ' <span class="category-count">(' . absint( $term->count ) . ')</span>';
                echo '</a>';
                echo '</li>';
            }
            
            echo '</ul>';
        } else {
            // Render non-breaking fallback paragraph to alert operators of empty lists
            echo '<p class="no-listings-found">' . esc_html__( 'No categories found matching criteria.', 'tsw-custom-listing' ) . '</p>';
        }

        // Output theme configuration layout closing-wrappers
        echo $args['after_widget'];
    }

    /**
     * 3. Backend Administrative Dashboard Controller View
     * Renders settings fields inside wp-admin/widgets.php or customizer drawers.
     */
    public function form( $instance ) {
        // Establish administrative fallbacks
        $title  = isset( $instance['title'] ) ? $instance['title'] : esc_html__( 'Listing Categories', 'tsw-custom-listing' );
        $number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'tsw-custom-listing' ); ?>
            </label>
            <input 
                class="widefat" 
                id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" 
                type="text" 
                value="<?php echo esc_attr( $title ); ?>" 
            />
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>">
                <?php esc_html_e( 'Number of categories to display:', 'tsw-custom-listing' ); ?>
            </label>
            <input 
                class="tiny-text" 
                id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" 
                type="number" 
                step="1" 
                min="1" 
                value="<?php echo esc_attr( $number ); ?>" 
                size="3" 
            />
        </p>
        <?php
    }

    /**
     * 4. State Validation & Update Processor
     * Sanitizes inputs safely before committing values to global wp_options records.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;

        // Clean values thoroughly to protect against cross-site scripting/injection attempts
        $instance['title']  = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['number'] = ( ! empty( $new_instance['number'] ) ) ? absint( $new_instance['number'] ) : 5;

        return $instance;
    }
}

/**
 * 5. Environment Core Workspace Hook Registration
 * Binds the widget class safely to the core execution chain loop.
 */
function tsw_custom_listing_register_widgets() {
    register_widget( 'TSW_Listing_Categories_Widget' );
}
add_action( 'widgets_init', 'tsw_custom_listing_register_widgets' );