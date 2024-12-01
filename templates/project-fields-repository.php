<?php
$short_description = get_post_meta( $post->ID, '_short_description', true );
$project_repository = get_post_meta( $post->ID, '_osprojects_project_repository', true );
$project_license = get_post_meta( $post->ID, '_osprojects_project_license', true );
$stable_release_version = get_post_meta( $post->ID, '_osprojects_stable_release_version', true );
$stable_release_link = get_post_meta( $post->ID, '_osprojects_stable_release_link', true );
$development_release_version = get_post_meta( $post->ID, '_osprojects_development_release_version', true );
$development_release_link = get_post_meta( $post->ID, '_osprojects_development_release_link', true );
?>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="osprojects_project_repository"><?php _e( 'Git Repository', 'osprojects' ); ?></label>
        </th>
        <td>
            <input type="url" name="osprojects_project_repository" id="osprojects_project_repository" value="<?php echo esc_attr( $project_repository ); ?>" class="regular-text" />
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="short_description"><?php _e( 'Short Description', 'osprojects' ); ?></label>
        </th>
        <td>
            <input type="text" name="short_description" id="short_description" value="<?php echo esc_attr( $short_description ); ?>" class="regular-text" readonly />
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="osprojects_project_license"><?php _e( 'License', 'osprojects' ); ?></label>
        </th>
        <td>
            <input type="text" name="osprojects_project_license" id="osprojects_project_license" value="<?php echo esc_attr( $project_license ); ?>" class="regular-text" readonly />
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="osprojects_stable_release_version"><?php _e( 'Stable Release Version', 'osprojects' ); ?></label>
        </th>
        <td>
            <input type="text" name="osprojects_stable_release_version" id="osprojects_stable_release_version" value="<?php echo esc_attr( $stable_release_version ); ?>" class="regular-text" readonly />
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="osprojects_stable_release_link"><?php _e( 'Stable Release Link', 'osprojects' ); ?></label>
        </th>
        <td>
            <input type="url" name="osprojects_stable_release_link" id="osprojects_stable_release_link" value="<?php echo esc_attr( $stable_release_link ); ?>" class="regular-text" readonly />
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="osprojects_development_release_version"><?php _e( 'Development Release Version', 'osprojects' ); ?></label>
        </th>
        <td>
            <input type="text" name="osprojects_development_release_version" id="osprojects_development_release_version" value="<?php echo esc_attr( $development_release_version ); ?>" class="regular-text" readonly />
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="osprojects_development_release_link"><?php _e( 'Development Release Link', 'osprojects' ); ?></label>
        </th>
        <td>
            <input type="url" name="osprojects_development_release_link" id="osprojects_development_release_link" value="<?php echo esc_attr( $development_release_link ); ?>" class="regular-text" readonly />
        </td>
    </tr>
</table>
