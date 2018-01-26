<?php

function frc_post_transient_deletion ($post_id) {
    frc_api_remove_post_class_type($post_id);
    
    frc_api_delete_transients_in_group("post_" . $post_id);
    frc_api_delete_transients_in_group("wp_query");
}

//Let's make sure we destroy transients after saving a post
add_action('save_post', "frc_post_transient_deletion");
