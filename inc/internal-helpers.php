<?php

function frc_api_get_transient_group_list ($transient_group) {
    $query_list = get_transient("_frc_group_" . $transient_group);

    if(empty($query_list) || is_string($query_list))
        return [];

    return $query_list;
}

function frc_api_set_transient_group_list ($transient_group, $list) {
    set_transient("_frc_group_" . $transient_group, $list);
}

function frc_api_add_transient_to_group_list ($transient_group, $transient) {
    $transients = frc_api_get_transient_group_list($transient_group);
    $transients[] = $transient;
    frc_api_set_transient_group_list($transient_group, $transients);
}

function frc_api_delete_transients_in_group ($transient_group) {
    $list = frc_api_get_transient_group_list($transient_group);

    foreach($list as $transient) {
        delete_transient($transient);
    }

    frc_api_set_transient_group_list($transient_group, []);
}

function frc_api_get_post_class_type ($post_id) {
    $class_types = get_transient('_frc_post_class_types');

    if(isset($class_types[$post_id]))
        return $class_types[$post_id];
    
    return false;
}

function frc_api_set_post_class_type ($post_id, $class_type) {
    $class_types = get_transient('_frc_post_class_types');

    $class_types[$post_id] = $class_type;

    set_transient('_frc_post_class_types', $class_types);

    return true;
}

function frc_api_remove_post_class_type($post_id) {
    $class_types = get_transient('_frc_post_class_types');

    unset($class_types[$post_id]);

    set_transient('_frc_post_class_types', $class_types);

    return true;
}

function frc_api_name_to_key ($name) {
    $name = str_replace("-", "_", $name);
    $name = preg_replace("/[^a-zA-Z0-9\_]+/", "_", $name);
    $name = trim($name, "_");
    return strtolower($name);
} 

function frc_api_class_name_to_proper ($class_name) {
    if(is_object($class_name))
        $class_name = get_class($class_name);

    $class_name_parts = explode("_", str_replace("-", "_", $class_name));
    $post_type_proper_name = implode(" ", $class_name_parts);
    return $post_type_proper_name;
}

function frc_api_proof_acf_schema_groups ($acf_schema_groups) {
    if(!isset($acf_schema_groups['key']))
        $acf_schema_groups['key'] = md5(frc_api_name_to_key($acf_schema_groups['title']));

    $acf_schema_groups['fields'] = frc_api_proof_acf_schema($acf_schema_groups['fields'], $acf_schema_groups['key'] . '_fields');

    return $acf_schema_groups;
}

function frc_api_proof_acf_schema ($acf_schema, $prefix, $flexible = false) {
    foreach($acf_schema as $key => $field) {
        if(!isset($field['key'])) {
            $acf_schema[$key]['key'] = md5($prefix . "_" . $field['name']);
        }

        if(isset($field['sub_fields'])) {
            $acf_schema[$key]['sub_fields'] = frc_api_proof_acf_schema($field['sub_fields'], $prefix . "_" . $field['name']);
        }

        if(isset($field['layouts'])) {
            $acf_schema[$key]['layouts'] = frc_api_proof_acf_schema($field['layouts'], $prefix . '_' . $key, true);
        }
    }

    return $acf_schema;
}

function frc_api_render ($file, $data = [], $cache_result_hooks = false) {
    $transient_key = '_frc_render_' . md5($file . serialize($data));

    if(!$cache_result_hooks || ($required_data = get_transient($transient_key)) === false) {
        ob_start();
        extract((array) $data);

        require_once $file;
        $required_data = ob_get_clean();

        if($cache_result_hooks) {
            set_transient($transient_key, $required_data, WEEK_IN_SECONDS);
            frc_api_add_render_transient_data($transient_key, $cache_result_hooks);
        }
    }

    return $required_data;
}

function frc_api_get_base_class_children ($base_class = false) {
    $additional_classes = FRC_Framework::get_instance()->additional_classes;
    $excluded_classes = FRC_Framework::get_instance()->excluded_classes;

    if(!$base_class)
        return [];

    $output = [];

    $declared_classes = get_declared_classes();

    foreach($declared_classes as $class_name) {
        if(get_parent_class($class_name) != $base_class
            || (isset($excluded_classes[$base_class])
                && is_array($excluded_classes[$base_class])
                && in_array($class_name, $excluded_classes[$base_class])))
            continue;

        $output[frc_api_name_to_key($class_name)] = $class_name;
    }

    if($additional_classes) {
        foreach($additional_classes as $class_name) {
            if(isset($excluded_classes[$base_class])
                && is_array($excluded_classes[$base_class])
                && in_array($class_name, $excluded_classes[$base_class]))
                continue;

            $output[frc_api_name_to_key($class_name)] = $class_name;
        }
    }

    return $output;
}

function frc_api_get_render_transient_data () {
    return get_transient("_frc_render_transient_data") ? get_transient("_frc_render_transient_data") : [];
}

function frc_api_set_render_transient_data($data) {
    return set_transient("_frc_render_transient_data", $data);
}

function frc_api_add_render_transient_data ($transient_key, $hooks) {
    $transient_data = frc_api_get_render_transient_data();

    $transient_data[$transient_key] = $hooks;

    frc_api_set_render_transient_data($transient_data);
}

function frc_api_get_component_path ($component) {
    return FRC_Framework::get_instance()->component_data_locations[$component] ?? false;
}

function frc_api_load_components_in_directory ($components_directory) {
    $component_data_locations = FRC_Framework::get_instance()->component_data_locations;

    $components_directory = rtrim($components_directory, "/");

    $contents = array_diff(scandir($components_directory), ['..', '.']);

    $component_dirs = [];
    foreach($contents as $content) {
        $dir = $components_directory . '/' . $content;

        if(is_dir($dir)) {
            if(file_exists($dir . '/component.php') && file_exists($dir . '/view.php')) {
                $component_data_locations[$content] = $dir;

                require_once $dir . '/component.php';
            } else {
                trigger_error("Found component directory (" . $dir . "), but it doesn't contain both component.php and view.php -files.", E_USER_ERROR);
            }
        }
    }

    FRC_Framework::get_instance()->component_data_locations = $component_data_locations;
}

function frc_api_get_components_of_types ($component_types) {
    $component_types = (is_string($component_types)) ? [$component_types] : $component_types;
   
    $components = [];
    foreach(frc_api_get_base_class_children("FRC_Component_Base_Class") as $component) {
        $reference_class = new $component();

        if(!array_intersect($reference_class->get_component_types(), $component_types))
            continue;

        $components[] = $component;
    }

    return $components;
}