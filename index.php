<?php
declare(strict_types=1);

get_header();
?>

<main id="main" class="site-main">
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
                            &mdash; <?php the_author(); ?>
                        </div>
                    </header>
                    <div class="entry-summary">
                        <?php the_excerpt(); ?>
                    </div>
                    <footer class="entry-footer">
                        <a href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read more', 'dynamo' ); ?></a>
                    </footer>
                </article>
            <?php endwhile; ?>

            <?php the_posts_navigation(); ?>

        <?php else : ?>

            <p><?php esc_html_e( 'No posts found.', 'dynamo' ); ?></p>

        <?php endif; ?>

    </div>
</main>

<?php get_footer();
