<?php
declare(strict_types=1);

get_header();

$dynamo_layout = Dynamo_Options::get_layout_mode();
$dynamo_has_sidebar = in_array( $dynamo_layout, [ 'sidebar-left', 'sidebar-right' ], true );
?>

<main id="main" class="site-main">
    <div class="dynamo-container dynamo-content-wrap<?php echo $dynamo_has_sidebar ? ' dynamo-has-sidebar' : ''; ?>">

        <?php if ( $dynamo_has_sidebar && $dynamo_layout === 'sidebar-left' ) : ?>
            <aside class="widget-area dynamo-sidebar"><?php dynamic_sidebar( 'sidebar-1' ); ?></aside>
        <?php endif; ?>

        <div class="dynamo-primary">
            <?php while ( have_posts() ) : the_post(); ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
                        <h1 class="entry-title"><?php the_title(); ?></h1>
                        <div class="entry-meta">
                            <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                            &mdash; <?php the_author(); ?>
                        </div>
                    </header>

                    <div class="entry-content">
                        <?php
                        the_content();
                        wp_link_pages();
                        ?>
                    </div>

                    <footer class="entry-footer">
                        <?php the_tags( '<p>' . esc_html__( 'Tags: ', 'dynamo' ), ', ', '</p>' ); ?>
                    </footer>
                </article>

                <?php
                the_post_navigation( [
                    'prev_text' => esc_html__( '&larr; Previous post', 'dynamo' ),
                    'next_text' => esc_html__( 'Next post &rarr;', 'dynamo' ),
                ] );
                ?>

                <?php comments_template(); ?>

            <?php endwhile; ?>
        </div>

        <?php if ( $dynamo_has_sidebar && $dynamo_layout === 'sidebar-right' ) : ?>
            <aside class="widget-area dynamo-sidebar"><?php dynamic_sidebar( 'sidebar-1' ); ?></aside>
        <?php endif; ?>

    </div>
</main>

<?php get_footer();
