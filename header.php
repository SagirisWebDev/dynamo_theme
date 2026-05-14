<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if ( is_singular() && pings_open() ) : ?>
        <link rel="pingback" href="<?php echo esc_url( get_bloginfo( 'pingback_url' ) ); ?>">
    <?php endif; ?>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'dynamo' ); ?></a>

<header id="masthead" class="site-header<?php echo Dynamo_Options::is_feature_enabled( 'sticky_header' ) ? ' dynamo-sticky-header' : ''; ?>">
    <div class="dynamo-container">
        <?php if ( has_custom_logo() ) : ?>
            <div class="site-branding-logo"><?php the_custom_logo(); ?></div>
        <?php else : ?>
            <?php if ( is_front_page() && is_home() ) : ?>
                <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
            <?php else : ?>
                <p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></p>
            <?php endif; ?>
            <?php
            $description = get_bloginfo( 'description', 'display' );
            if ( $description ) :
            ?>
                <p class="site-description"><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>
        <?php endif; ?>
        <?php
        wp_nav_menu( [
            'theme_location'  => 'primary',
            'menu_id'         => 'primary-menu',
            'container'       => 'div',
            'container_class' => 'menu-primary-container',
            'fallback_cb'     => false,
        ] );
        ?>
    </div>
</header>
