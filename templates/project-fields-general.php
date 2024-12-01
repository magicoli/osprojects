<?php
$project_website = get_post_meta( $post->ID, '_osprojects_project_website', true );
?>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="osprojects_project_website"><?php _e( 'Project Website', 'osprojects' ); ?></label>
        </th>
        <td>
            <input type="url" name="osprojects_project_website" id="osprojects_project_website" value="<?php echo esc_attr( $project_website ); ?>" class="regular-text" />
        </td>
    </tr>
</table>
