<?php

function frc_api_get_wp_query_list () {
    $query_list = get_transient("_frc_wp_queries");

    if(empty($query_list) || is_string($query_list))
        return [];

    return $query_list;
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

function frc_api_set_wp_query_list ($list) {
    set_transient("_frc_wp_queries", $list);
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
        $acf_schema_groups['key'] = frc_api_proper_name_to_key($acf_schema_groups['title']);

    $acf_schema_groups['fields'] = frc_api_proof_acf_schema($acf_schema_groups['fields'], $acf_schema_groups['key'] . '_fields');

    return $acf_schema_groups;
}

function frc_api_proof_acf_schema ($acf_schema, $prefix) {
    foreach($acf_schema as $key => $field) {
        if(!isset($field['key'])) {
            $acf_schema[$key]['key'] = $prefix . "_" . $field['name'];
        }

        if(isset($field['sub_fields'])) {
            $acf_schema[$key]['sub_fields'] = frc_api_proof_acf_schema($field['sub_fields'], $prefix . "_" . $field['name']);
        }
    }

    return $acf_schema;
}

function frc_api_get_base_post_children () {
    global $frc_additional_classes, $frc_excluded_classes;

    $output = [];

    $declared_classes = get_declared_classes();

    foreach($declared_classes as $class_name) {
        if(get_parent_class($class_name) != 'FRC_Post_Base_Class' || in_array($class_name, $frc_excluded_classes))
            continue;

        $output[frc_api_name_to_key($class_name)] = $class_name;
    }

    if($frc_additional_classes) {
        foreach($frc_additional_classes as $class_name) {
            if(in_array($class_name, $frc_excluded_classes))
                continue;

            $output[frc_api_name_to_key($class_name)] = $class_name;
        }
    }

    return $output;
}