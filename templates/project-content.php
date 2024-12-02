<?php
/**
 * Template for displaying project post type content.
**/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

OSProjects::enqueue_styles( 'osprojects-project', 'css/project.css' );
$project_fields = array(
    'osp_project_last_release_html' => __( 'Release', 'osprojects' ),
    'osp_project_last_commit_html' => __( 'Last Commit', 'osprojects' ),
    'osp_project_repository' => __( 'Repository', 'osprojects' ),
    'osp_project_website' => __( 'Official Website', 'osprojects' ),
    'osp_project_license' => __( 'License', 'osprojects' ),
);
?>
<div class="wp-entry-content osprojects osp-project">
    <table class="wp-list-table widefat fixed">
        <?php foreach ( $project_fields as $meta_key => $label ) : 
            $value = get_post_meta( get_the_ID(), $meta_key, true );
            if ( ! empty( $value ) ) : ?>
                <tr>
                    <th><?php echo $label; ?></th>
                    <td>
                        <?php 
                        if ( in_array( $meta_key, array( 'osp_project_last_release_html', 'osp_project_last_commit_html' ) ) ) {
                            echo $value;
                        } elseif (wp_http_validate_url( $value )) {
                            printf(
                                '<a href="%1$s" target="_blank">%1$s</a>',
                                esc_url( $value )
                            );
                        } else {
                            echo esc_html( $value ); 
                        }
                        ?>
                    </td>
                </tr>
            <?php endif; 
        endforeach; ?>
    </table>
    <?php echo $content; ?>
</div>
