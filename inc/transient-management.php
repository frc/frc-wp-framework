<?php

function frc_render_transient_management () {
    foreach(frc_api_get_render_transient_data() as $transient_key => $transient_hooks) {
        foreach($transient_hooks as $hook) {
            add_action($hook, function () use ($transient_key) {
                delete_transient($transient_key);
            });
        }
    }
}

add_action('init', "frc_render_transient_management");

function frc_post_transient_deletion ($post_id) {
    frc_api_remove_post_class_type($post_id);

    frc_api_delete_transients_in_group("post_" . $post_id);
    frc_api_delete_transients_in_group("wp_query");
}

//Let's make sure we destroy transients after saving a post
add_action('save_post', "frc_post_transient_deletion");
