<?php
declare(strict_types=1);

get_header();
?>

<main id="main" class="site-main">
    <div class="dynamo-container dynamo-content-wrap">

        <section class="error-404 not-found">
            <header class="page-header">
                <h1 class="page-title"><?php esc_html_e( 'Page not found', 'dynamo' ); ?></h1>
            </header>

            <div class="page-content">
                <p><?php esc_html_e( "The page you're looking for doesn't exist. Try searching below.", 'dynamo' ); ?></p>
                <?php get_search_form(); ?>
            </div>
        </section>

    </div>
</main>

<?php get_footer();
