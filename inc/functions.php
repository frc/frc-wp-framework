<?php

function frc_set_options ($options = [], $override = false) {
    $current_options = FRC_Framework::get_instance()->options;

    $last_options = $current_options ?? [];

    if(!$override)
        FRC_Framework::get_instance()->options = array_replace_recursive($last_options, $options);
    else
        FRC_Framework::get_instance()->options = $options;
}

function frc_get_options () {
    return FRC_Framework::get_instance()->options;
}

function frc_get_from_local_cache_stack ($post_id) {
    return FRC_Framework::get_instance()->frc_get_from_local_cache_stack($post_id);
}

function frc_add_to_local_cache_stack ($post) {
    FRC_Framework::get_instance()->add_to_local_cache_stack($post);
}

function frc_set_local_cache_stack ($posts) {
    FRC_Framework::get_instance()->set_local_cache_stack($post);
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

    $whole_object_transient_key = "_frc_post_whole_object_" . $post_id;
    
    if($frc_options['cache_whole_post_objects'] && !$get_fresh) {
        if(($post = get_transient($whole_object_transient_key)) !== false) {
            $post->remove_unused_post_data();
            $post->served_from_cache = true;
            frc_add_to_local_cache_stack($post);
            return $post;
        }
    }

    //Save the class of the post so we don't have to figure it out every time
    if(($post_class_to_use = frc_api_get_post_class_type($post_id)) === false) {
        $children = frc_api_get_base_class_children("FRC_Post_Base_Class");

        if(isset($children[get_post_type($post_id)])) {
            $post_class_to_use = $children[get_post_type($post_id)];
        } else {
            $post_class_to_use = $frc_options['default_frc_post_class'] ?? "FRC_Post";
        }

        frc_api_set_post_class_type($post_id, $post_class_to_use);
    }

    if($frc_options['cache_whole_post_objects']) {
        $post_class_args = [
            'cache_whole_object'    => false,
            'cache_acf_fields'      => false,
            'cache_categories'      => false,
            'cache_component_list'  => false
        ];
    }

    $post = new $post_class_to_use($post_id, $post_class_args);

    if($frc_options['cache_whole_post_objects']) {

        set_transient($whole_object_transient_key, $post);

        frc_api_add_transient_to_group_list("post_" . $post_id, $whole_object_transient_key);
    }

    frc_add_to_local_cache_stack($post);

    return $post;
}

function frc_get_render ($file, $data = [], $cache_result_hooks = false) {
    $trace_back = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

    $dir = dirname($trace_back[1]['file']);

    $file = $dir . "/" . trim($file, "/");
    return frc_api_render($file, $data, $cache_result_hooks);
}

function frc_render ($file, $data = [], $cache_result_hooks = false) {
    echo frc_get_render($file, $data, $cache_result_hooks);
}

function frc_add_class ($class_name, $base_class) {
    FRC_Framework::get_instance()->additional_classes[$base_class][] = $class_name;
}

function frc_exclude_class ($class_name, $base_class) {
    FRC_Framework::get_instance()->excluded_classes[$base_class][] = $class_name;
}