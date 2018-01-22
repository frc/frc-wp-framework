<?php

function frc_post_transient_deletion ($post_id) {
    delete_transient('_frc_api_post_object_' . $post_id);
    delete_transient('_frc_api_post_object_extra_data_' . $post_id);
    delete_transient('_frc_post_whole_object_' . $post_id);
    delete_transient('_frc_post_type_class_' . $post_id);
    delete_transient('_frc_api_post_acf_field_' . $post_id);
    delete_transient('_frc_api_post_categories_' . $post_id);
}

//Let's make sure we destroy transients after saving a post
add_action('save_post', "frc_post_transient_deletion");
