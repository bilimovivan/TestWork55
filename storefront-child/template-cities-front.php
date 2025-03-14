<?php
/**
 * Template Name: Cities Front Page
 * Description: Шаблон для вывода записей "cities" на главной странице.
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php
        // Получаем записи custom post type "cities"
        $args = array(
            'post_type'      => 'cities',
            'posts_per_page' => 10,
            'paged'          => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
        );
        $cities_query = new WP_Query( $args );

        if ( $cities_query->have_posts() ) :
            while ( $cities_query->have_posts() ) : $cities_query->the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
                        <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    </header>
                    <div class="entry-content">
                        <?php the_excerpt(); ?>
                    </div>
                </article>
                <?php
            endwhile;
            the_posts_pagination();
            wp_reset_postdata();
        else :
            echo '<p>' . __( 'There are no cities to display.', 'textdomain' ) . '</p>';
        endif;
        ?>

    </main>
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
