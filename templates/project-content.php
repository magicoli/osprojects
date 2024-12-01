<?php
/**
 * Template for displaying project post type content.
**/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

$project_fields = array(
    'osp_project_repository' => __( 'Repository', 'osprojects' ),
    'osp_project_last_release_html' => __( 'Release', 'osprojects' ),
    'osp_project_last_commit_html' => __( 'Last Commit', 'osprojects' ),
    'osp_project_license' => __( 'License', 'osprojects' ),
);
?>

<div>
    <h2 class="short_description">
        <?php echo esc_html( get_post_meta( get_the_ID(), 'osp_project_shortdesc', true ) ); ?>
    </h2>
    <table>
        <?php foreach ( $project_fields as $meta_key => $label ) : 
            $value = get_post_meta( get_the_ID(), $meta_key, true );
            if ( ! empty( $value ) ) : ?>
                <tr>
                    <th><?php echo $label; ?></th>
                    <td>
                        <?php 
                        if ( in_array( $meta_key, array( 'osp_project_last_release_html', 'osp_project_last_commit_html' ) ) ) : 
                            echo $value;
                        else : 
                            echo esc_html( $value ); 
                        endif; 
                        ?>
                    </td>
                </tr>
            <?php endif; 
        endforeach; ?>
    </table>
    <?php echo $content; ?>
</div>
