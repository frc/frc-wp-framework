<?php

/*
    Let's construct and manage everything
*/

function frc_api_manage_setup_components ($post_type, $component_types, $post_type_proper_name, $override_proper_name = null) {
    global $frc_component_setups;

    if(!empty($component_types)) {
        if(is_string($component_types))
            $component_types = [$component_types];
        
        $component_acf_fields = [];

        $frc_component_setups[$post_type]['types'] = $component_types;

        foreach($component_types as $component_type) {
            foreach(frc_api_get_components_of_type($component_type) as $component) {
                $component_reference_class = new $component();

                $component_key = frc_api_name_to_key($component);

                $component_acf_fields[$component_key] = [
                    'name'       => $component_key,
                    'label'      => frc_api_class_name_to_proper($component),
                    'sub_fields' => $component_reference_class->acf_schema
                ];

                $frc_component_setups[$post_type]['components'][$component_key] = $component;
            }
        }
        
        if(!empty($component_acf_fields)) {
            $component_field_group_args = frc_api_proof_acf_schema_groups([
                'title'     => $override_proper_name ?? $post_type_proper_name . ' Components',
                'fields'    => [
                    [
                        'key'       => $post_type . '_components',
                        'label'     => 'Components',
                        'name'      => 'frc_components',
                        'type'      => 'flexible_content',
                        'layouts'   => $component_acf_fields
                    ]
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => $post_type,
                        ]
                    ]
                ]
            ]);

            acf_add_local_field_group($component_field_group_args);
        }
    }
}

function frc_api_manage_custom_post_types () {
    foreach(frc_api_get_base_class_children("FRC_Post_Base_Class") as $post_type_key_name => $class_name) {
        $reference_class = new $class_name();

        $post_type_proper_name = $reference_class->options['proper_name'] ?? frc_api_class_name_to_proper($class_name);

        $post_type_key_name = $reference_class->options['key_name'] ?? $post_type_key_name;

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
                
                acf_add_local_field_group($options_acf_groups);
            } else {   
                $options_acf_fields = $reference_class->acf_schema;
                
                //Construct the acf fields
                if(isset($options_acf_fields) && !empty($options_acf_fields)) {
                    $field_group_key = str_replace("_", "", $post_type_key_name . '_fields');
                    
                    $options_acf_fields = frc_api_proof_acf_schema($options_acf_fields, $field_group_key);
                    
                    acf_add_local_field_group(frc_api_proof_acf_schema_groups([
                        'title'     => $reference_class->options['acf_group_name'] ?? $post_type_proper_name . ' Fields',
                        'fields'    => $options_acf_fields, 
                        'location' => [
                            [
                                [
                                    'param' => 'post_type',
                                    'operator' => '==',
                                    'value' => $post_type_key_name,
                                ]
                            ]
                        ]
                    ]));
                }
            }

            //Component initializations
            $component_types = $reference_class->component_types ?? [];

            frc_api_manage_setup_components($post_type_key_name, $component_types, $post_type_proper_name, $reference_class->options['components_proper_name']);
        }
    }

    frc_api_manage_setup_components('post', ['basic'], 'post', 'Post');
    frc_api_manage_setup_components('page', ['basic'], 'page', 'page');
}

add_action('init', "frc_api_manage_custom_post_types");
