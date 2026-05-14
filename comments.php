<?php
declare(strict_types=1);

if ( post_password_required() ) {
    return;
}
?>

<section id="comments" class="comments-area">

    <?php if ( have_comments() ) : ?>

        <h2 class="comments-title">
            <?php
            $comments_number = get_comments_number();
            if ( $comments_number === 1 ) {
                printf(
                    /* translators: %s: post title */
                    esc_html__( 'One thought on &ldquo;%s&rdquo;', 'dynamo' ),
                    '<span>' . esc_html( get_the_title() ) . '</span>'
                );
            } else {
                printf(
                    /* translators: 1: comment count, 2: post title */
                    esc_html( _n( '%1$s thought on &ldquo;%2$s&rdquo;', '%1$s thoughts on &ldquo;%2$s&rdquo;', $comments_number, 'dynamo' ) ),
                    esc_html( number_format_i18n( $comments_number ) ),
                    '<span>' . esc_html( get_the_title() ) . '</span>'
                );
            }
            ?>
        </h2>

        <ol class="comment-list">
            <?php
            wp_list_comments( [
                'style'      => 'ol',
                'short_ping' => true,
                'avatar_size'=> 48,
            ] );
            ?>
        </ol>

        <?php
        the_comments_navigation( [
            'prev_text' => esc_html__( '&larr; Older comments', 'dynamo' ),
            'next_text' => esc_html__( 'Newer comments &rarr;', 'dynamo' ),
        ] );
        ?>

        <?php if ( ! comments_open() ) : ?>
            <p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'dynamo' ); ?></p>
        <?php endif; ?>

    <?php endif; ?>

    <?php comment_form(); ?>

</section>
