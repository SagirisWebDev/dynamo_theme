<?php
declare(strict_types=1);

get_header();
?>

<main id="main" class="site-main">

    <header class="page-header">
        <div class="dynamo-container">
            <h1 class="page-title">
                <?php
                printf(
                    /* translators: %s: search query */
                    esc_html__( 'Search results for: %s', 'dynamo' ),
                    '<span>' . esc_html( get_search_query() ) . '</span>'
                );
                ?>
            </h1>
        </div>
    </header>

    <div class="dynamo-container">

        <?php if ( have_posts() ) : ?>

            <?php while ( have_posts() ) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
                    <header class="entry-header">
                        <h2 class="entry-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        <div class="entry-meta">
                            <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                        </div>
                    </header>
                    <div class="entry-summary">
                        <?php the_excerpt(); ?>
                    </div>
                </article>
            <?php endwhile; ?>

            <?php the_posts_navigation(); ?>

        <?php else : ?>

            <p><?php esc_html_e( 'No results found.', 'dynamo' ); ?></p>
            <?php get_search_form(); ?>

        <?php endif; ?>

    </div>
</main>

<?php get_footer();
