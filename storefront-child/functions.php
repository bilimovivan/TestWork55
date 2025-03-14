<?php
/**
 * Файл функций дочерней темы Storefront Child
 * Description: Здесь добавлен весь кастомный функционал согласно тестовому заданию.
 */

 add_filter( 'use_block_editor_for_post', '__return_false', 100 );

/*-------------------------------------------
 1. Регистрация кастомного типа записи "Cities"
--------------------------------------------*/
function register_cities_cpt() {
    $labels = array(
        'name'               => __( 'Cities', 'textdomain' ),
        'singular_name'      => __( 'City', 'textdomain' ),
        'add_new'            => __( 'Add New City', 'textdomain' ),
        'add_new_item'       => __( 'Add New City', 'textdomain' ),
        'edit_item'          => __( 'Edit City', 'textdomain' ),
        'new_item'           => __( 'New City', 'textdomain' ),
        'all_items'          => __( 'All Cities', 'textdomain' ),
        'view_item'          => __( 'View City', 'textdomain' ),
        'search_items'       => __( 'Search Cities', 'textdomain' ),
        'not_found'          => __( 'No cities found', 'textdomain' ),
        'not_found_in_trash' => __( 'No cities found in Trash', 'textdomain' ),
        'menu_name'          => __( 'Cities', 'textdomain' ),
    );
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array( 'slug' => 'cities' ),
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
        'show_in_rest'       => true,
    );
    register_post_type( 'cities', $args );
}
add_action( 'init', 'register_cities_cpt' );


/*-------------------------------------------
 2. Регистрация кастомной таксономии "Countries"
--------------------------------------------*/
function register_countries_taxonomy() {
    $labels = array(
        'name'              => __( 'Countries', 'textdomain' ),
        'singular_name'     => __( 'Country', 'textdomain' ),
        'search_items'      => __( 'Search Countries', 'textdomain' ),
        'all_items'         => __( 'All Countries', 'textdomain' ),
        'parent_item'       => __( 'Parent Country', 'textdomain' ),
        'parent_item_colon' => __( 'Parent Country:', 'textdomain' ),
        'edit_item'         => __( 'Edit Country', 'textdomain' ),
        'update_item'       => __( 'Update Country', 'textdomain' ),
        'add_new_item'      => __( 'Add New Country', 'textdomain' ),
        'new_item_name'     => __( 'New Country Name', 'textdomain' ),
        'menu_name'         => __( 'Countries', 'textdomain' ),
    );
    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'rewrite'           => array( 'slug' => 'countries' ),
    );
    register_taxonomy( 'countries', 'cities', $args );
}
add_action( 'init', 'register_countries_taxonomy' );


/*-------------------------------------------
 3. Добавление метабоксов для ввода latitude и longitude
--------------------------------------------*/
function add_cities_metaboxes() {
    add_meta_box(
        'city_location_metabox',
        __( 'City Location', 'textdomain' ),
        'render_city_location_metabox',
        'cities',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'add_cities_metaboxes' );

function render_city_location_metabox( $post ) {
    // Создаём nonce для проверки при сохранении
    wp_nonce_field( 'save_city_location', 'city_location_nonce' );
    
    // Получаем сохранённые значения
    $latitude = get_post_meta( $post->ID, 'latitude', true );
    $longitude = get_post_meta( $post->ID, 'longitude', true );
    ?>
    <p>
        <label for="city_latitude"><?php _e( 'Latitude:', 'textdomain' ); ?></label>
        <input type="text" id="city_latitude" name="city_latitude" value="<?php echo esc_attr( $latitude ); ?>" />
    </p>
    <p>
        <label for="city_longitude"><?php _e( 'Longitude:', 'textdomain' ); ?></label>
        <input type="text" id="city_longitude" name="city_longitude" value="<?php echo esc_attr( $longitude ); ?>" />
    </p>
    <?php
}

function save_city_location_metabox( $post_id ) {
    // Проверка nonce
    if ( ! isset( $_POST['city_location_nonce'] ) || ! wp_verify_nonce( $_POST['city_location_nonce'], 'save_city_location' ) ) {
        return;
    }
    // Исключаем автосохранения
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // Сохраняем данные с санитизацией
    if ( isset( $_POST['city_latitude'] ) ) {
        update_post_meta( $post_id, 'latitude', sanitize_text_field( $_POST['city_latitude'] ) );
    }
    if ( isset( $_POST['city_longitude'] ) ) {
        update_post_meta( $post_id, 'longitude', sanitize_text_field( $_POST['city_longitude'] ) );
    }
}
add_action( 'save_post', 'save_city_location_metabox' );


/*-------------------------------------------
 4. Виджет "City Weather Widget"
--------------------------------------------*/
class City_Weather_Widget extends WP_Widget {
    function __construct() {
        parent::__construct(
            'city_weather_widget',
            __( 'City Weather Widget', 'textdomain' ),
            array( 'description' => __( 'Displays selected city weather using OpenWeatherMap API', 'textdomain' ) )
        );
    }
    
    // Форма настроек виджета в админке
    function form( $instance ) {
        $selected_city = ! empty( $instance['selected_city'] ) ? $instance['selected_city'] : '';
        // Получаем список городов
        $cities = get_posts( array(
            'post_type'      => 'cities',
            'posts_per_page' => -1,
        ) );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'selected_city' ); ?>"><?php _e( 'Select City:', 'textdomain' ); ?></label>
            <select id="<?php echo $this->get_field_id( 'selected_city' ); ?>" name="<?php echo $this->get_field_name( 'selected_city' ); ?>">
                <option value=""><?php _e( 'Select a city', 'textdomain' ); ?></option>
                <?php foreach ( $cities as $city ) : ?>
                    <option value="<?php echo esc_attr( $city->ID ); ?>" <?php selected( $selected_city, $city->ID ); ?>>
                        <?php echo esc_html( get_the_title( $city->ID ) ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }
    
    // Сохранение настроек виджета
    function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['selected_city'] = ! empty( $new_instance['selected_city'] ) ? intval( $new_instance['selected_city'] ) : '';
        return $instance;
    }
    
    // Вывод виджета на сайте
    function widget( $args, $instance ) {
        echo $args['before_widget'];
        
        if ( ! empty( $instance['selected_city'] ) ) {
            $city_id   = $instance['selected_city'];
            $city_name = get_the_title( $city_id );
            $latitude  = get_post_meta( $city_id, 'latitude', true );
            $longitude = get_post_meta( $city_id, 'longitude', true );
            
            // Получаем данные погоды через внешний API
            $weather = get_city_weather( $latitude, $longitude );
            
            echo '<div class="city-weather-widget">';
            echo '<h3>' . esc_html( $city_name ) . '</h3>';
            if ( $weather && isset( $weather['main']['temp'] ) ) {
                echo '<p>' . __( 'Current Temperature: ', 'textdomain' ) . esc_html( $weather['main']['temp'] ) . '°C</p>';
            } else {
                echo '<p>' . __( 'Weather data not available', 'textdomain' ) . '</p>';
            }
            echo '</div>';
        }
        
        echo $args['after_widget'];
    }
}

// Регистрация виджета
function register_city_weather_widget() {
    register_widget( 'City_Weather_Widget' );
}
add_action( 'widgets_init', 'register_city_weather_widget' );


// Функция для получения данных погоды (используется OpenWeatherMap API. Версия 2.5)
function get_city_weather( $lat, $lon ) {
    if ( empty( $lat ) || empty( $lon ) ) {
        return false;
    }
    
    $transient_key = 'weather_' . md5( $lat . '_' . $lon );
    $weather = get_transient( $transient_key );
    if ( false !== $weather ) {
        return $weather;
    }

    $api_key = 'f7823710895da52cf399c975c1095fc1';
    $url = "https://api.openweathermap.org/data/2.5/weather?lat=" . urlencode( $lat ) . "&lon=" . urlencode( $lon ) ."&units=metric&appid=" . $api_key;
    
    $response = wp_remote_get( $url );
    if ( is_wp_error( $response ) ) {
        return false;
    }
    
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    if ( ! empty( $data ) ) {
        // Кэшируем данные на 30 минут
        set_transient( $transient_key, $data, 30 * MINUTE_IN_SECONDS );
    }
    return $data;
}


/*-------------------------------------------
 5. AJAX-поиск городов (wp_ajax)
--------------------------------------------*/
function ajax_search_cities() {
    // Проверка nonce для безопасности
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cities_search_nonce' ) ) {
        wp_die( __( 'Nonce verification failed', 'textdomain' ) );
    }
    
    $search_term = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
    global $wpdb;
    $posts_table      = $wpdb->posts;
    $meta_table       = $wpdb->postmeta;
    $terms_table      = $wpdb->terms;
    $term_relationships = $wpdb->term_relationships;
    $term_taxonomy    = $wpdb->term_taxonomy;
    
    $search_sql = '';
    if ( ! empty( $search_term ) ) {
        $search_sql = $wpdb->prepare( " AND p.post_title LIKE %s", '%' . $wpdb->esc_like( $search_term ) . '%' );
    }
    
    $query = "
        SELECT t.name as country, p.ID, p.post_title, pm_lat.meta_value as latitude, pm_lon.meta_value as longitude
        FROM $posts_table p
        LEFT JOIN $meta_table pm_lat ON ( p.ID = pm_lat.post_id AND pm_lat.meta_key = 'latitude' )
        LEFT JOIN $meta_table pm_lon ON ( p.ID = pm_lon.post_id AND pm_lon.meta_key = 'longitude' )
        LEFT JOIN $term_relationships tr ON ( p.ID = tr.object_id )
        LEFT JOIN $term_taxonomy tt ON ( tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'countries' )
        LEFT JOIN $terms_table t ON ( tt.term_id = t.term_id )
        WHERE p.post_type = 'cities' AND p.post_status = 'publish'
        $search_sql
    ";
    $cities = $wpdb->get_results( $query );
    
    if ( $cities ) {
        ob_start();
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
            </tbody>
        </table>
        <?php
        $output = ob_get_clean();
        echo $output;
    } else {
        echo '<p>' . __( 'No cities found.', 'textdomain' ) . '</p>';
    }
    wp_die();
}
add_action( 'wp_ajax_search_cities', 'ajax_search_cities' );
add_action( 'wp_ajax_nopriv_search_cities', 'ajax_search_cities' );


/*-------------------------------------------
 6. Подключение JavaScript для AJAX-поиска
--------------------------------------------*/
function enqueue_cities_scripts() {
    if ( is_page_template( array( 'template-cities.php', 'template-cities-front.php' ) ) ) {
        wp_enqueue_script( 'cities-search', get_stylesheet_directory_uri() . '/js/cities-search.js', array( 'jquery' ), '1.0', true );
        wp_localize_script( 'cities-search', 'citiesAjax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'cities_search_nonce' ),
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'enqueue_cities_scripts' );

