<?php
namespace FRC;

function api_transient_name ($name) {
    return $name . (function_exists("pll_current_language")) ? ' ' . pll_current_language() : '';
}

function api_get_transient_group_list ($transient_group) {
    $query_list = get_option("_frc_transient_group_" . $transient_group);

    if(empty($query_list))
        return [];

    return $query_list;
}

function api_set_transient_group_list ($transient_group, $list) {
    if(empty($list))
        return;

    update_option("_frc_transient_group_" . $transient_group, $list);
}

function api_add_transient_to_group_list ($transient_group, $transient) {
    $transients = api_get_transient_group_list($transient_group);
    $transients[] = $transient;
    api_set_transient_group_list($transient_group, $transients);
}

function api_delete_transients_in_group ($transient_group) {
    $list = api_get_transient_group_list($transient_group);

    if(!empty($list) && is_array($list)) {
        foreach ($list as $transient) {
            delete_transient($transient);
        }
    }

    api_set_transient_group_list($transient_group, []);
}

function api_get_post_class_type ($post_id) {
    $class_types = get_transient('_frc_post_class_types');

    if(isset($class_types[$post_id]))
        return $class_types[$post_id];
    
    return false;
}

function api_set_post_class_type ($post_id, $class_type) {
    $class_types = get_transient('_frc_post_class_types');

    $class_types[$post_id] = $class_type;

    set_transient('_frc_post_class_types', $class_types);

    return true;
}

function api_remove_post_class_type($post_id) {
    $class_types = (!empty(get_transient('_frc_post_class_types'))) ? get_transient('_frc_post_class_types') : [];

    unset($class_types[$post_id]);

    set_transient('_frc_post_class_types', $class_types);

    return true;
}

function api_name_to_key ($name) {
    $name = str_replace("-", "_", $name);
    $name = preg_replace("/[^a-zA-Z0-9\_]+/", "_", $name);
    $name = trim($name, "_");
    return strtolower($name);
} 

function api_name_to_proper ($class_name) {
    if(is_object($class_name))
        $class_name = get_class($class_name);

    $class_name_parts = explode("_", str_replace("-", "_", $class_name));
    $post_type_proper_name = implode(" ", $class_name_parts);
    return $post_type_proper_name;
}

function api_proof_acf_schema_groups ($acf_schema_groups, $prefix = "") {
    if(!isset($acf_schema_groups['key']))
        $acf_schema_groups['key'] = $prefix . api_name_to_key($acf_schema_groups['title']);

    $acf_schema_groups['fields'] = api_proof_acf_schema($acf_schema_groups['fields'], $prefix . $acf_schema_groups['key'] . '_fields');

    return $acf_schema_groups;
}

function api_proof_acf_schema_item ($acf_schema_item, $prefix, $key = 0) {
    if (!isset($acf_schema_item['key'])) {
        $acf_schema_item['key'] = $prefix . "_" . $acf_schema_item['name'];
    }

    if (isset($acf_schema_item['sub_fields'])) {
        $acf_schema_item['sub_fields'] = api_proof_acf_schema($acf_schema_item['sub_fields'], $prefix . "_" . $acf_schema_item['name']);
    }

    if (isset($acf_schema_item['layouts'])) {
        $acf_schema_item['layouts'] = api_proof_acf_schema($acf_schema_item['layouts'], $prefix . '_' . $key, true);
    }

    return $acf_schema_item;
}

function api_proof_acf_schema ($acf_schema, $prefix) {
    foreach($acf_schema as $key => $field) {
        $acf_schema[$key] = api_proof_acf_schema_item($acf_schema[$key], $prefix, $key);
    }

    return $acf_schema;
}

function api_validate_acf_schema_item ($schema_item) {
    $valid_acf_types = [];

    foreach(acf_get_field_types() as $field_types) {
        $valid_acf_types = array_merge($valid_acf_types, array_keys($field_types));
    }

    $errors = [];

    $required_keys = [
        'name',
        'label',
        'type',
        'key'
    ];

    foreach($required_keys as $key) {
        if(!isset($schema_item[$key])) {
            return 'ACF Schema key: ' . $key . ' is not defined.';
        }
    }

    $type = $schema_item['type'] ?? '';

    if(!in_array($type, $valid_acf_types)) {
        return 'ACF Schema item type is not a valid one. Currently the type is: ' . $type . '. The valid types are: ' . implode(", ", $valid_acf_types) . '.';
    }

    if($type == 'repeater') {
        if(isset($schema_item['sub_fields']) && !empty($schema_item['sub_fields'])) {
            foreach($schema_item['sub_fields'] as $schema_child_item) {
                $errors[] = [
                    api_validate_acf_schema_item($schema_child_item),
                    $schema_child_item
                ];

                if($errors) {
                    return $errors;
                }
            }
        }
    }

    return $errors;
}

function api_validate_acf_schema ($schema) {
    $errors = [];

    foreach($schema as $schema_item) {
        $validation = api_validate_acf_schema_item($schema_item);

        if(empty($validation)) {
            continue;
        }

        $errors[] = [
            $validation,
            $schema_item
        ];

        if($errors) {
            return $errors;
        }
    }

    return $errors;
}

function api_render ($file, $data = [], $extract = false) {
    if($extract) {
        extract((array) $data);
    } else {
        $data = Render_Data::prepare($data);
    }

    ob_start();

    include $file;

    return ob_get_clean();
}

function api_get_component_path ($component) {
    return FRC::get_instance()->component_locations[$component] ?? false;
}
