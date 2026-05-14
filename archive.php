<?php
declare(strict_types=1);

get_header();

$dynamo_layout = Dynamo_Options::get_layout_mode();
$dynamo_has_sidebar = in_array( $dynamo_layout, [ 'sidebar-left', 'sidebar-right' ], true );
?>

<main id="main" class="site-main" tabindex="-1">

    <header class="archive-header">
        <div class="dynamo-container">
            <h1 class="archive-title">
                <?php
                if ( is_category() ) {
                    single_cat_title();
                } elseif ( is_tag() ) {
                    single_tag_title();
                } elseif ( is_author() ) {
                    the_author();
                } elseif ( is_year() ) {
                    echo esc_html( get_the_date( 'Y' ) );
                } elseif ( is_month() ) {
                    echo esc_html( get_the_date( 'F Y' ) );
                } elseif ( is_day() ) {
                    echo esc_html( get_the_date() );
                } else {
                    esc_html_e( 'Archives', 'dynamo' );
                }
                ?>
            </h1>
            <?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
        </div>
    </header>

    <div class="dynamo-container dynamo-content-wrap<?php echo $dynamo_has_sidebar ? ' dynamo-has-sidebar' : ''; ?>">

        <?php if ( $dynamo_has_sidebar && $dynamo_layout === 'sidebar-left' ) : ?>
            <aside class="widget-area dynamo-sidebar"><?php dynamic_sidebar( 'sidebar-1' ); ?></aside>
        <?php endif; ?>

        <div class="dynamo-primary">
            <?php Dynamo_Breadcrumbs::render(); ?>
            <?php if ( have_posts() ) : ?>

                <?php while ( have_posts() ) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <a class="entry-featured-image" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1"><?php the_post_thumbnail( 'medium_large' ); ?></a>
                        <?php endif; ?>
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
                    </article>
                <?php endwhile; ?>

                <?php the_posts_navigation(); ?>

            <?php else : ?>

                <p><?php esc_html_e( 'No posts found.', 'dynamo' ); ?></p>

            <?php endif; ?>
        </div>

        <?php if ( $dynamo_has_sidebar && $dynamo_layout === 'sidebar-right' ) : ?>
            <aside class="widget-area dynamo-sidebar"><?php dynamic_sidebar( 'sidebar-1' ); ?></aside>
        <?php endif; ?>

    </div>
</main>

<?php get_footer();
