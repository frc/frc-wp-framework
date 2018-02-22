<?php
namespace FRC;

function post_transient_deletion ($post_id) {
    api_remove_post_class_type($post_id);
    api_delete_transients_in_group("post_" . $post_id);
    api_delete_transients_in_group("wp_query");
}

//Let's make sure we destroy transients after saving a post
add_action('save_post', "FRC\post_transient_deletion", 10, 1);

function taxonomy_transient_deletion ($term_id, $taxonomy) {
    $posts = get_posts([
        'numberposts' => -1,
        'tax_query' => [
            [
                'taxonomy' => $taxonomy,
                'fields'   => 'id',
                'terms'    => $term_id
            ]
        ]
    ]);

    foreach($posts as $post) {
        post_transient_deletion($post->ID);
    }
}

//Let's also make sure that when the categories are saved, the posts transients
//that have that category, also gets deleted.
add_action('edited_terms', 'FRC\taxonomy_transient_deletion', 10, 2);