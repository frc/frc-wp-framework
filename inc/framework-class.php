<?php

class FRC {
    public $options;

    public $component_setups;
    public $component_data_locations;
    public $component_classes;
    public $component_root_folders;

    public $local_cache_stack;
    public $additional_classes;
    public $excluded_classes;

    public $custom_post_types;

    public function __construct () {
        add_action('init', [$this, "collect_custom_post_types"]);

        add_action('init', [$this, "setup_custom_post_types"]);

        if(defined("ACF_PRO"))
            add_action('init', [$this, "setup_basic_post_type_components"]);
    }

    static public function get_instance () {
        global $frc_framework_instance;

        if(!$frc_framework_instance) {
            $frc_framework_instance = new FRC();
        }

        return $frc_framework_instance;
    }
    
    public function setup_basic_post_type_components () {
        if($this->options['setup_basic_post_type_components']) {
            $this->setup_components('post', [['types' => 'post-component']], 'Post');
            $this->setup_components('page', [['types' => 'post-component']], 'Page');
        }
    }

    public function collect_custom_post_types () {
        $this->custom_post_types = frc_api_get_base_class_children("FRC_Post_Base_Class");
    }

    public function setup_custom_post_types () {
        foreach($this->custom_post_types as $post_type_key_name => $class_name) {
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
                if(is_array($reference_class->component_setups) && !empty($reference_class->component_setups))
                    $this->setup_components($post_type_key_name, $reference_class->component_setups, $post_type_proper_name);
            }
        }
    }

    public function setup_components ($post_type, $component_setups, $proper_name) {
        if(empty($component_setups) || !is_array($component_setups))
            return;

        $current_index = 0;

        foreach($component_setups as $component_setup) {
            if(!isset($component_setup['types']))
                continue;

            if(is_string($component_setup['types']))
                $component_setup['types'] = [$component_setup['types']];

            if(empty($component_setup['types']))
                continue;

            $component_acf_fields = [];
            
            $component_setup_list = [];

            foreach(frc_get_components_of_types($component_setup['types']) as $component) {
                $component_reference_class = new $component();

                $component_key = md5('group_' . $current_index . '_' . frc_api_name_to_key($component));
                
                $component_setup_list['components'][frc_api_name_to_key($component)] = $component;

                $component_acf_fields[$component_key] = [
                    'key'        => $component_key,
                    'name'       => frc_api_name_to_key($component),
                    'label'      => frc_api_class_name_to_proper($component),
                    'sub_fields' => $component_reference_class->acf_schema
                ];
            }

            if(empty($component_acf_fields))
                continue;

            $this->component_setups[$post_type][] = $component_setup_list;
            
            $component_field_group_args = frc_api_proof_acf_schema_groups([
                'title'     => $proper_name . ' Components',
                'fields'    => [
                    [
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
            ], $current_index . '_group_');

            $component_field_group_args = array_replace_recursive($component_field_group_args, $component_setup['acf_group_schema'] ?? []);

            acf_add_local_field_group($component_field_group_args);

            $current_index++;
        }
    }

    public function add_to_local_cache_stack ($post) {
        if(isset($this->local_cache_stack[$post->ID]))
            return;
    
        $this->local_cache_stack[$post->ID] = $post;
    
        if(count($this->local_cache_stack) > $this->options['local_cache_stack_size'])
            array_shift($this->local_cache_stack);
    }

    public function set_local_cache_stack ($posts) {
        $cache_posts = [];
        foreach($posts as $post) {
            $cache_posts[$post->ID] = $post;
        }
    
        $this->local_cache_stack = $cache_posts;
    }

    public function frc_get_from_local_cache_stack($post_id) {
        return $this->local_cache_stack[$post_id] ?? false;
    }

    public function register_component_class ($class_name, $directory = "") {
        $this->component_classes[] = $class_name;

        if(!empty($directory))
            $this->component_locations[$class_name] = $directory;
    }

    static public function use_cache () {
        return FRC::get_instance()->options['use_caching'];
    }
}