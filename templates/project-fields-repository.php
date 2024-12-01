<?php
$short_description = get_post_meta( $post->ID, 'osp_project_shortdesc', true );
$project_repository = get_post_meta( $post->ID, 'osp_project_repository', true );
$project_license = get_post_meta( $post->ID, 'osp_project_license', true );
$project_release = get_post_meta( $post->ID, 'osp_project_last_release_html', true ); 
$last_commit_html = get_post_meta( $post->ID, 'osp_project_last_commit_html', true );
?>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="osp_project_repository"><?php _e( 'Git Repository', 'osprojects' ); ?></label>
        </th>
        <td>
            <input type="url" name="osp_project_repository" id="osp_project_repository" value="<?php echo esc_attr( $project_repository ); ?>" class="regular-text" />
            <p id="osp_project_repository_notification" class="description notification" aria-live="polite"></p>
            <p>
                <label><?php _e( 'Release: ', 'osprojects' ); ?></label>
                <span id="osp_project_release"><?php echo $project_release; ?></span>
            </p>
            <p>
                <label><?php _e( 'Last Commit: ', 'osprojects' ); ?></label>
                <span id="osp_project_last_commit"><?php echo $last_commit_html; ?></span>
            </p>
            <p>
                <label><?php _e( 'License: ', 'osprojects' ); ?></label>
                <span id="osp_project_license"><?php echo esc_html( $project_license ); ?></span>
            </p>
        </td>
    </tr>
</table>
