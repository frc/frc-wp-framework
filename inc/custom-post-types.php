<?php

/*
    Let's construct and manage everything
*/
function frc_api_manage_custom_post_types () {
    foreach(frc_api_get_base_class_children("FRC_Post_Base_Class") as $post_type_key_name => $class_name) {
        $reference_class = new $class_name();

        $post_type_proper_name = $reference_class->proper_name ?? frc_api_class_name_to_proper($class_name);

        $post_type_key_name = $reference_class->key_name ?? $post_type_key_name;

        $default_register_post_type_args = [
            'labels' => [
                'name'              => $post_type_proper_name,
                'singular_name'     => $post_type_proper_name,
                'menu_name'         => $post_type_proper_name
            ],
            'description'           => "Automaticaly generated post type",
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'query_var'             => true,
            'rewrite'               => array( 'slug' => $post_type_key_name ),
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => null,
            'supports'              => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
        ];

        $options_args = $reference_class->args ?? [];

        $register_post_type_args = array_replace_recursive($default_register_post_type_args, $options_args);

        register_post_type($post_type_key_name, $register_post_type_args);

        $options_taxonomies = $reference_class->taxonomies;

        //Construct the taxonomies
        if(isset($options_taxonomies) && is_array($options_taxonomies)) {
            foreach($options_taxonomies as $taxonomy_key => $taxonomy) {
                $taxonomy_name = $taxonomy;

                if(is_array($taxonomy)) {
                    $taxonomy_name = $taxonomy_key;
                }

                $default_custom_taxonomy_args = [
                    'labels' => [
                        'name'          => ucfirst($taxonomy_name),
                        'singular_name' => ucfirst($taxonomy_name)
                    ],
                    'show_ui'           => true,
                    'show_admin_column' => true,
                    'query_var'         => true,
                    'rewrite'           => array( 'slug' => $taxonomy_name ),
                ];

                $taxonomy_args = $default_custom_taxonomy_args;

                if(is_array($taxonomy))
                    $taxonomy_args = array_replace_recursive($default_custom_taxonomy_args, $taxonomy);

                register_taxonomy($taxonomy_name, [$post_type_key_name], $taxonomy_args);
            }
        }

        if(function_exists('acf_add_local_field_group')) {
            $options_acf_groups = $reference_class->acf_schema_groups ?? false;

            if($options_acf_groups) {
                $options_acf_groups = frc_api_proof_acf_schema_groups($options_acf_groups);
                $options_acf_groups = frc_api_acf_schema_groups_components($options_acf_groups);

                acf_add_local_field_group($options_acf_groups);
            } else {   
                $options_acf_fields = $reference_class->acf_schema;
                
                //Construct the acf fields
                if(isset($options_acf_fields) && !empty($options_acf_fields)) {
                    $field_group_key = str_replace("_", "", $post_type_key_name . '_fields');
                    
                    $options_acf_fields = frc_api_acf_schema_components($options_acf_fields, $field_group_key);
                    
                    $options_acf_fields = frc_api_proof_acf_schema($options_acf_fields, $field_group_key);
                    
                    acf_add_local_field_group([
                        'key'       => $field_group_key,
                        'title'     => $reference_class->acf_group_name ?? $post_type_proper_name . ' Fields',
                        'fields'    => $options_acf_fields, 
                        'location' => array (
                            array (
                                array (
                                    'param' => 'post_type',
                                    'operator' => '==',
                                    'value' => $post_type_key_name,
                                ),
                            ),
                        )
                    ]);
                }
            }
        }
    }
}

add_action('init', "frc_api_manage_custom_post_types");
