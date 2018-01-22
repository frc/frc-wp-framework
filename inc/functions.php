<?php

function frc_set_options ($options = [], $override = false) {
    global $frc_options;

    $last_options = $frc_options ?? [];

    if(!$override)
        $frc_options = array_replace_recursive($last_options, $options);
    else
        $frc_options = $options;
}

function frc_get_options () {
    global $frc_options;

    return $frc_options;
}

function frc_get_from_local_cache_stack ($post_id) {
    global $frc_local_cache_stack;
    
    return $frc_local_cache_stack[$post_id] ?? false;
}

function frc_add_to_local_cache_stack ($post) {
    global $frc_local_cache_stack;

    if(isset($frc_local_cache_stack[$post->ID]))
        return;

    $frc_local_cache_stack[$post->ID] = $post;

    if(count($frc_local_cache_stack) > 10)
        array_shift($frc_local_cache_stack);
}

function frc_get_post ($post_id = null, $get_fresh = false) {
    if(($post = frc_get_from_local_cache_stack($post_id)) !== false) {
        $post->remove_unused_post_data();
        return $post;
    }

    $frc_options = frc_get_options();

    if(is_object($post_id) && $post_id instanceof WP_Post)
        $post_id = $post_id->ID;
    else if(empty($post_id) && isset($GLOBALS['post']))
        $post_id = $GLOBALS['post']->ID;

    if($frc_options['cache_whole_post_objects'] && !$get_fresh) {
        if(($post = get_transient("_frc_post_whole_object_" . $post_id)) !== false) {
            $post->remove_unused_post_data();
            frc_add_to_local_cache_stack($post);
            return $post;
        }
    }

    //Save the class of the post so we don't have to figure it out every time
    if(($post_class_to_use = frc_api_get_post_class_type($post_id)) === false) {
        $children = frc_api_get_base_post_children();

        if(isset($children[get_post_type($post_id)])) {
            $post_class_to_use = $children[get_post_type($post_id)];
        } else {
            $post_class_to_use = $frc_options['default_frc_post_class'] ?? "FRC_Post";
        }

        frc_api_set_post_class_type($post_id, $post_class_to_use);
    }

    if($frc_options['cache_whole_post_objects']) {
        $post_class_args = [
            'cache_whole_object' =>  false
        ];
    }

    $post = new $post_class_to_use($post_id, $post_class_args);

    if($frc_options['cache_whole_post_objects']) {
        set_transient("_frc_post_whole_object_" . $post_id, $post);
    }

    frc_add_to_local_cache_stack($post);

    return $post;
}

function frc_add_class ($class_name) {
    global $frc_additional_classes;

    $frc_additional_classes[] = $class_name;
}

function frc_exclude_class ($class_name) {
    global $frc_excluded_classes;

    $frc_excluded_classes[] = $class_name;
}