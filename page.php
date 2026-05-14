<?php
declare(strict_types=1);

get_header();

$dynamo_layout = Dynamo_Options::get_layout_mode();
$dynamo_has_sidebar = in_array( $dynamo_layout, [ 'sidebar-left', 'sidebar-right' ], true );
?>

<main id="main" class="site-main" tabindex="-1">
    <div class="dynamo-container dynamo-content-wrap<?php echo $dynamo_has_sidebar ? ' dynamo-has-sidebar' : ''; ?>">

        <?php if ( $dynamo_has_sidebar && $dynamo_layout === 'sidebar-left' ) : ?>
            <aside class="widget-area dynamo-sidebar"><?php dynamic_sidebar( 'sidebar-1' ); ?></aside>
        <?php endif; ?>

        <div class="dynamo-primary">
            <?php Dynamo_Breadcrumbs::render(); ?>
            <?php while ( have_posts() ) : the_post(); ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
                        <h1 class="entry-title"><?php the_title(); ?></h1>
                    </header>

                    <?php if ( has_post_thumbnail() ) : ?>
                        <figure class="entry-featured-image"><?php the_post_thumbnail( 'large' ); ?></figure>
                    <?php endif; ?>

                    <div class="entry-content">
                        <?php
                        the_content();
                        wp_link_pages();
                        ?>
                    </div>
                </article>

                <?php comments_template(); ?>

            <?php endwhile; ?>
        </div>

        <?php if ( $dynamo_has_sidebar && $dynamo_layout === 'sidebar-right' ) : ?>
            <aside class="widget-area dynamo-sidebar"><?php dynamic_sidebar( 'sidebar-1' ); ?></aside>
        <?php endif; ?>

    </div>
</main>

<?php get_footer();
