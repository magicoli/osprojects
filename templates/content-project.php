
<?php
/**
 * Template for displaying project post type content.
**/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>

<div>
    <h2 class=short_description>
        <?php echo esc_html( get_post_meta( get_the_ID(), '_short_description', true ) ); ?>
    </h2>
    <?php echo $content; ?>
</div>
