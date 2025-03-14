<?php
/**
 * Template Name: Cities Table
 * Description: Отображает таблицу со списком стран, городов и текущей температурой.
 */

get_header(); ?>

<div class="cities-table-container">
    <?php do_action( 'before_cities_table' ); ?>
    
    <h2><?php _e( 'Cities Table', 'textdomain' ); ?></h2>
    <p>
        <input type="text" id="city-search" placeholder="<?php _e( 'Search cities...', 'textdomain' ); ?>">
        <button id="city-search-btn"><?php _e( 'Search', 'textdomain' ); ?></button>
    </p>
    
    <div id="cities-table-result">
        <?php
        global $wpdb;
        $posts_table      = $wpdb->posts;
        $meta_table       = $wpdb->postmeta;
        $terms_table      = $wpdb->terms;
        $term_relationships = $wpdb->term_relationships;
        $term_taxonomy    = $wpdb->term_taxonomy;
        
        $query = "
            SELECT t.name as country, p.ID, p.post_title, pm_lat.meta_value as latitude, pm_lon.meta_value as longitude
            FROM $posts_table p
            LEFT JOIN $meta_table pm_lat ON ( p.ID = pm_lat.post_id AND pm_lat.meta_key = 'latitude' )
            LEFT JOIN $meta_table pm_lon ON ( p.ID = pm_lon.post_id AND pm_lon.meta_key = 'longitude' )
            LEFT JOIN $term_relationships tr ON ( p.ID = tr.object_id )
            LEFT JOIN $term_taxonomy tt ON ( tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'countries' )
            LEFT JOIN $terms_table t ON ( tt.term_id = t.term_id )
            WHERE p.post_type = 'cities' AND p.post_status = 'publish'
        ";
        $cities = $wpdb->get_results( $query );
        ?>
        <table border="1" width="100%">
            <thead>
                <tr>
                    <th><?php _e( 'Country', 'textdomain' ); ?></th>
                    <th><?php _e( 'City', 'textdomain' ); ?></th>
                    <th><?php _e( 'Temperature (°C)', 'textdomain' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $cities ) : ?>
                    <?php foreach ( $cities as $city ) : 
                        $temp = '';
                        if ( ! empty( $city->latitude ) && ! empty( $city->longitude ) ) {
                            $weather = get_city_weather( $city->latitude, $city->longitude );
                            if ( $weather && isset( $weather['main']['temp'] ) ) {
                                $temp = $weather['main']['temp'] . '°C';
                            } else {
                                $temp = __( 'N/A', 'textdomain' );
                            }
                        } else {
                            $temp = __( 'N/A', 'textdomain' );
                        }
                    ?>
                        <tr>
                            <td><?php echo esc_html( $city->country ); ?></td>
                            <td><?php echo esc_html( $city->post_title ); ?></td>
                            <td><?php echo esc_html( $temp ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3"><?php _e( 'No cities found.', 'textdomain' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php do_action( 'after_cities_table' ); ?>
</div>

<?php get_footer(); ?>
