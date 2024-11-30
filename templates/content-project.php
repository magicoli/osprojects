<?php
/**
 * Template for displaying project post type content.
**/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

$project_fields = array(
    'project_website' => __( 'Project Website', 'osprojects' ),
    'project_repository' => __( 'Project Repository', 'osprojects' ),
    'project_license' => __( 'License', 'osprojects' ),
    'stable_release_version' => __( 'Stable Release Version', 'osprojects' ),
    'stable_release_link' => __( 'Stable Release Link', 'osprojects' ),
    'development_release_version' => __( 'Development Release Version', 'osprojects' ),
    'development_release_link' => __( 'Development Release Link', 'osprojects' ),
);
?>

<div>
    <h2 class="short_description">
        <?php echo esc_html( get_post_meta( get_the_ID(), '_short_description', true ) ); ?>
    </h2>
    <table>
        <?php foreach ( $project_fields as $field => $label ) : 
            $value = get_post_meta( get_the_ID(), '_osprojects_' . $field, true );
            if ( ! empty( $value ) ) : ?>
                <tr>
                    <th><?php echo $label; ?></th>
                    <td>
                        <?php if ( in_array( $field, array( 'stable_release_link', 'development_release_link' ) ) ) : 
                            $version_field = str_replace( '_link', '_version', $field );
                            $version = get_post_meta( get_the_ID(), '_osprojects_' . $version_field, true );
                            if ( ! empty( $version ) ) : ?>
                                <a href="<?php echo esc_url( $value ); ?>"><?php echo esc_html( $version ); ?></a>
                            <?php endif; 
                        else : 
                            echo esc_html( $value ); 
                        endif; ?>
                    </td>
                </tr>
            <?php endif; 
        endforeach; ?>
    </table>
    <?php echo $content; ?>
</div>
