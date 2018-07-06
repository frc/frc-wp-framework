<?php
namespace FRC;

function FRC () {
    return FRC::get_instance();
}

function set_options ($options = [], $override = false) {
    $set_options = \apply_filters("frc_framework_set_options", $options);

    if(empty($set_options)) {
        $default_options = [
            'override_post_type_classes' => [
                'post' => 'FRC\Post',
                'page' => 'FRC\Post'
            ],
            'local_cache_stack_size' => 20,
            'cache_whole_post_objects' => true,
            'setup_basic_post_type_components' => true,
            'use_caching' => false
        ];

        $options_filtered = \apply_filters("frc_framework_options", $options);

        $options = array_replace_recursive($default_options, FRC()->options ?? [], $options, (is_array($options_filtered)) ? $options_filtered : []);
    } else {
        $options = $set_options;
    }

    if(!$override)
        FRC()->options = $options;
    else
        FRC()->options = $options;
}

function get_options () {
    return FRC()->options;
}

function get_from_local_cache_stack ($post_id) {
    return FRC()->get_from_local_cache_stack($post_id);
}

function add_to_local_cache_stack ($post) {
    FRC()->add_to_local_cache_stack($post);
}

function set_local_cache_stack ($posts) {
    FRC()->set_local_cache_stack($posts);
}

function get_post ($post_id = null, $get_fresh = false) : Post_Base_Class {

    if(is_object($post_id) && $post_id instanceof \WP_Post) {
        $post_id = $post_id->ID;
    } else if(empty($post_id) && isset($GLOBALS['post'])) {
        $post_id = $GLOBALS['post']->ID;
    }

    if(($post = get_from_local_cache_stack($post_id)) !== false) {
        $post->remove_unused_post_data();
        return $post;
    }

    $frc_options = get_options();

    $whole_object_transient_key = "_frc_post_whole_object_" . $post_id;
    if(FRC::use_cache() && $frc_options['cache_whole_post_objects'] && !$get_fresh) {
        if(($post = get_transient($whole_object_transient_key)) !== false) {
            $post->remove_unused_post_data();
            $post->served_from_cache = true;
            add_to_local_cache_stack($post);
            return $post;
        }
    }

    //Save the class of the post so we don't have to figure it out every time
    if(FRC::use_cache() || ($post_class_to_use = api_get_post_class_type($post_id)) === false) {
        $children = FRC()->get_post_type_classes();

        if(isset($children[get_post_type($post_id)])) {
            $post_class_to_use = $children[get_post_type($post_id)];
        } else {
            $post_class_to_use = $frc_options['default_post_class'] ?? "FRC\Post";
        }

        if(FRC::use_cache()) {
            api_set_post_class_type($post_id, $post_class_to_use);
        }
    }

    $post_class_args = [];

    if($frc_options['cache_whole_post_objects']) {
        $post_class_args = [
            'cache_whole_object'    => false,
            'cache_acf_fields'      => false,
            'cache_categories'      => false,
            'cache_component_list'  => false
        ];
    }

    $post = new $post_class_to_use($post_id, $post_class_args);

    if(FRC::use_cache() && $frc_options['cache_whole_post_objects']) {

        set_transient($whole_object_transient_key, $post);

        api_add_transient_to_group_list("post_" . $post_id, $whole_object_transient_key);
    }

    add_to_local_cache_stack($post);

    return $post;
}

function get_posts ($args, $cache_results = false) {
    $args = array_replace_recursive([
        'posts_per_page' => -1
    ], $args);

    $query = new Query($args, $cache_results);

    return $query->get_posts();
}

function get_term ($term_id) {
    if(is_object($term_id)) {
        if ($term_id instanceof \WP_Term) {
            $term_id = $term_id->term_id;
        } else if($term_id instanceof Term) {
            return $term_id;
        }
    }

    $transient_key = "_frc_taxonomy_whole_object_" . $term_id;
    if((FRC::use_cache() && ($term_object = get_transient($transient_key)) === false) || !FRC::use_cache()) {
        $term_object = new Term($term_id);

        if(FRC::use_cache()) {
            set_transient($transient_key, $term_object);
            api_add_transient_to_group_list("term_" . $term_id, $transient_key);
            api_add_transient_to_group_list("terms", $transient_key);
        }
    }

    return $term_object;
}

function register_folders ($folders = []) {
    $default_folders = [
        'post-types'     => 'frc/content-types/post-types',
        'components'     => 'frc/content-types/components',
        'taxonomies'     => 'frc/content-types/taxonomies',
        'migrations'     => 'frc/migrations',
        'options'        => 'frc/options'
    ];

    $folders = \apply_filters("frc_framework_register_folders", $default_folders);

    $folder_schema = [
        'post-types'     => 'FRC\register_post_types_folder',
        'components'     => 'FRC\register_components_folder',
        'taxonomies'     => 'FRC\register_taxonomies_folder',
        'migrations'     => 'FRC\register_migrations_folder',
        'options'        => 'FRC\register_options_folder'
    ];

    foreach($folders as $folder_key => $folder_value) {
        if(!in_array($folder_key, array_keys($folder_schema)))
            continue;

        if(is_array($folder_value)) {
            foreach($folder_value as $folder) {
                $folder_schema[$folder_key]($folder);
            }
        } else if (is_string($folder_value)) {
            $folder_schema[$folder_key]($folder_value);
        }
    }
}

function register_post_types_folder ($directory) {
    $frc_framework = FRC();

    $directory = get_stylesheet_directory() . '/' . ltrim(rtrim($directory, "/"), "/");

    if(!file_exists($directory)) {
        return;
    }

    $frc_framework->root_folders['post-types'][] = $directory;

    foreach(glob($directory . '/*.php') as $file) {
        $class_name = pathinfo(basename($file), PATHINFO_FILENAME);

        require_once $file;

        if(!class_exists($class_name)) {
            trigger_error("Found custom post type file (" . $file . "), but not a class defined with the same name (" . $class_name . ").", E_USER_NOTICE);
            return;
        } else {
            $frc_framework->register_custom_post_type_class($class_name);
        }
    }
}

function register_components_folder ($directory) {
    $frc_framework = FRC();

    $directory = get_stylesheet_directory() . '/' . ltrim(rtrim($directory, "/"), "/");

    if(!file_exists($directory)) {
        return;
    }

    $frc_framework->root_folders['components'][] = $directory;

    $contents = array_diff(scandir($directory), ['..', '.']);

    foreach($contents as $content) {
        $dir = $directory . '/' . $content;

        if(is_dir($dir)) {
            if(file_exists($dir . '/' . $content . '.php') && file_exists($dir . '/view.php')) {
                require_once $dir . '/' . $content . '.php';

                if(!class_exists($content)) {
                    trigger_error("Found component directory and found all the proper files, but didn't find a class with the same name (" . $content . ").", E_USER_NOTICE);
                    return;
                } else {
                    $frc_framework->register_component_class($content, $dir);
                }
            } else {
                trigger_error("Found component directory (" . $dir . "), but it doesn't contain both " . $content . ".php and view.php -files.", E_USER_NOTICE);
            }
        }
    }
}

function register_taxonomies_folder ($directory) {
    $frc_framework = FRC();

    $directory = get_stylesheet_directory() . '/' . ltrim(rtrim($directory, "/"), "/");

    if(!file_exists($directory)) {
        return;
    }

    $frc_framework->root_folders['taxonomies'][] = $directory;

    foreach(glob($directory . '/*.php') as $file) {
        $class_name = pathinfo(basename($file), PATHINFO_FILENAME);

        require_once $file;

        if(!class_exists($class_name)) {
            trigger_error("Found custom taxonomy file (" . $file . "), but not a class defined with the same name (" . $class_name . ").", E_USER_NOTICE);
            return;
        } else {
            $frc_framework->register_taxonomy_class($class_name);
        }
    }

}

function register_options_folder ($directory) {
    $frc_framework = FRC();

    $directory = get_stylesheet_directory() . '/' . ltrim(rtrim($directory, "/"), "/");

    if (!file_exists($directory)) {
        return;
    }

    foreach (glob($directory . '/*.php') as $file) {
        $class_name = pathinfo(basename($file), PATHINFO_FILENAME);

        require_once $file;

        if (!class_exists($class_name)) {
            trigger_error("Found options file (" . $file . "), but not a class with the same name (" . $class_name . ").", E_USER_NOTICE);
            return;
        } else {
            $frc_framework->register_options_class($class_name);
        }
    }
}

function register_migrations_folder ($directory) {
    $frc_framework = FRC();

    $directory = get_stylesheet_directory() . '/' . ltrim(rtrim($directory, "/"), "/");

    if(!file_exists($directory)) {
        return;
    }

    $frc_framework->root_folders['migration'][] = $directory;
}

function register_post_type_components ($post_type, $component_setups, $proper_name) {
    return FRC()->register_post_type_components($post_type, $component_setups, $proper_name);
}

function get_registered_components () {
    return FRC()->component_classes;
}

function get_registered_taxonomies ($include_outside_taxonomies = false) {
    $taxonomies = array_map("strtolower", FRC()->taxonomy_classes);

    if($include_outside_taxonomies) {
        $taxonomies = array_merge($taxonomies, array_values(
                get_taxonomies([
                    'public' => true
                ])
            )
        );
    }

    return $taxonomies;
}

function get_posts_from_fields ($data, $use_frc_post = false) {
    $returned_posts = [];

    if(is_object($data) && $data instanceof \WP_Post) {
        if(!$use_frc_post) {
            return [$data];
        } else {
            return [get_post($data)];
        }
    } else if(isset($data['attachment'])) {
        if(!$use_frc_post) {
            return [\get_post($data['attachment']['ID'])];
        } else {
            return [get_post($data['attachment']['ID'])];
        }
    } else if(is_object($data) || is_array($data)) {
        if(is_object($data)) {
            $data = get_object_vars($data);
        }

        if(!empty($data)) {
            foreach($data as $key => $value) {
                $returned_posts = array_merge($returned_posts, get_posts_from_fields($value, $use_frc_post));
            }
        }
    }

    return $returned_posts;
}

function get_post_acf_fields_posts ($post_id, $use_frc_post = false) {
    $fields = get_fields($post_id);

    return get_posts_from_fields($fields, $use_frc_post);
}

function get_post_dependencies ($post_id) {
    return get_post_meta($post_id, 'frc_post_dependencies', true);
}

function set_post_dependencies ($post_id, $dependencies) {
    update_post_meta($post_id, 'frc_post_dependencies', $dependencies);
}

function delete_post_dependencies ($post_id) {
    delete_post_meta($post_id, 'frc_post_dependencies');
}

function calculate_post_dependency_graph ($post_id, $dependency_graph = []) {
    $post = get_post($post_id);

    $posts = get_post_acf_fields_posts($post_id, true);

    if(is_array($posts) && !empty($posts)) {
        foreach($posts as $post) {
            if(isset($dependency_graph[$post_id]) && in_array($post->ID, $dependency_graph[$post_id]['to'])) {
                continue;
            }

            $dependency_graph[$post_id]['to'][] = $post->ID;

            $dependency_graph = calculate_post_dependency_graph($post->ID, $dependency_graph);

            if(in_array($post_id, $dependency_graph[$post->ID]['to'])) {
                $dependency_graph[$post_id]['from'][] = $post->ID;
            }
        }
    }

    return $dependency_graph;
}

function calculate_post_dependencies ($post_id) {
    $dependency_graph = calculate_post_dependency_graph($post_id);

    if(!is_array($dependency_graph) || !empty($dependency_graph)) {
        return;
    }

    foreach($dependency_graph as $dep_post_id => $deps) {
        set_post_dependencies($dep_post_id, $deps);
    }
}

function flush_post_cache ($post_id, $posts_flushed = []) {
    $post_deps = get_post_dependencies($post_id);

    if(is_array($post_deps) && !empty($post_deps)){
        foreach($post_deps as $pd) {
            foreach($pd as $post_dep) {
                if (in_array($post_dep, $posts_flushed)) {
                    continue;
                }

                $posts_flushed[] = $post_dep;

                flush_post_cache($post_dep, $posts_flushed);
            }
        }
    }

    api_delete_transients_in_group('post_' . $post_id);
}

function add_component_to_post ($post_id, $component, $data) {
    $frc = FRC();

    if(!in_array($component, $frc->component_classes)) {
        return false;
    }

    $current_field_data = get_field(FRC_COMPONENTS_KEY, $post_id) ?? [];

    $merged_fields = array_merge([
        array_replace($data, [
            'acf_fc_layout' => api_name_to_key($component)
        ])
    ], $current_field_data);

    $update_field = \update_field(FRC_COMPONENTS_KEY, $merged_fields, $post_id);

    do_action('save_post', $post_id, \get_post($post_id), true);

    return true;
}
