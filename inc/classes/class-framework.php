<?php
namespace FRC;

class FRC {
    public $options;

    public $component_setups;
    public $component_data_locations;
    public $component_classes;

    public $taxonomies;
    public $post_types_to_taxonomies;

    public $custom_post_type_classes;
    public $options_classes;

    public $component_root_folders;
    public $custom_post_type_root_folders;

    public $local_cache_stack;
    public $additional_classes;
    public $excluded_classes;

    public function __construct () {
        add_action('init', [$this, "setup_custom_post_types"]);
        add_action('init', [$this, "setup_post_type_taxonomies"]);
    }

    static public function get_instance () {
        global $frc_framework_instance;

        if(!$frc_framework_instance) {
            $frc_framework_instance = new self();
        }

        return $frc_framework_instance;
    }

    private function setup_options_pages () {
        foreach($this->options_classes as $class_name) {
            $reference_class = new $class_name();

            $options_page_proper_name = $reference_class->options['proper_name'] ?? api_class_name_to_proper($class_name);

            $options_page_key_name = $reference_class->options['key_name'] ?? api_name_to_key($class_name);

            $default_args = array_replace_recursive([
                'page_title' 	=> $options_page_proper_name,
                'menu_title'	=> $options_page_proper_name,
                'menu_slug' 	=> $options_page_key_name,
                'capability'	=> 'edit_posts',
                'redirect'		=> false
            ], $reference_class->args);

            acf_add_options_page($default_args);
        }
    }

    public function setup_post_type_taxonomies () {
        if(!empty($this->taxonomies) && is_array($this->taxonomies)) {
            foreach ($this->taxonomies as $taxonomy_name => $taxonomy) {

                $post_types = $this->post_types_to_taxonomies[$taxonomy_name];

                if (empty($post_types))
                    continue;

                register_taxonomy($taxonomy_name, $post_types, $taxonomy);
            }
        }
    }

    public function setup_custom_post_types () {
        if(empty($this->custom_post_type_classes))
            return;

        foreach($this->get_post_type_classes() as $post_type_key_name => $class_name) {
            $reference_class = new $class_name();

            $post_type_proper_name = $reference_class->options['proper_name'] ?? api_class_name_to_proper($class_name);
            $post_type_key_name = $override_post_type_classes[$class_name] ?? $reference_class->options['key_name'] ?? $post_type_key_name;

            if($reference_class->create_post_type) {

                $default_register_post_type_args = [
                    'labels' => [
                        'name' => $post_type_proper_name,
                        'singular_name' => $post_type_proper_name,
                        'menu_name' => $post_type_proper_name
                    ],
                    'description' => "Automatically generated post type",
                    'public' => true,
                    'publicly_queryable' => true,
                    'show_ui' => true,
                    'show_in_menu' => true,
                    'query_var' => true,
                    'rewrite' => array('slug' => $post_type_key_name),
                    'capability_type' => 'post',
                    'has_archive' => true,
                    'hierarchical' => false,
                    'menu_position' => null,
                    'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments')
                ];

                $options_args = $reference_class->args ?? [];

                $register_post_type_args = array_replace_recursive($default_register_post_type_args, $options_args);

                register_post_type($post_type_key_name, $register_post_type_args);
            }

            if(isset($reference_class->taxonomies) && is_array($reference_class->taxonomies) && !empty($reference_class->taxonomies)) {
                $this->add_post_type_taxonomies_to_lists($reference_class->taxonomies, $post_type_key_name);
            }

            if(function_exists('acf_add_local_field_group')) {
                $options_acf_groups = $reference_class->acf_schema_groups ?? false;
    
                if($options_acf_groups) {
                    $options_acf_groups = api_proof_acf_schema_groups($options_acf_groups);
                    
                    acf_add_local_field_group($options_acf_groups);
                } else {   
                    $options_acf_fields = $reference_class->acf_schema;
                    
                    //Construct the acf fields
                    if(isset($options_acf_fields) && !empty($options_acf_fields)) {
                        $field_group_key = str_replace("_", "", $post_type_key_name . '_fields');
                        
                        $options_acf_fields = api_proof_acf_schema($options_acf_fields, $field_group_key);
                        
                        acf_add_local_field_group(api_proof_acf_schema_groups([
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
                if(!empty($reference_class->component_setups))
                    $this->register_post_type_components($post_type_key_name, $reference_class->component_setups, $post_type_proper_name);
            }
        }
    }

    private function add_post_type_taxonomies_to_lists ($taxonomies, $post_type_key_name) {

        //Setup taxonomy relationships
        if(is_array($taxonomies) && !empty($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {
                if(!isset($this->taxonomies[$taxonomy]))
                    continue;


                $this->post_types_to_taxonomies[$taxonomy][] = $post_type_key_name;
            }
        }

    }

    private function register_post_type_components ($post_type, $component_setups, $proper_name) {
        if(empty($component_setups))
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

            foreach(get_components_of_types($component_setup['types']) as $component) {
                $component_reference_class = new $component();

                $component_key = md5('group_' . $current_index . '_' . api_name_to_key($component));
                
                $component_setup_list['components'][api_name_to_key($component)] = $component;

                $component_acf_fields[$component_key] = [
                    'key'        => $component_key,
                    'name'       => api_name_to_key($component),
                    'label'      => $component_reference_class->proper_name ?? api_class_name_to_proper($component),
                    'sub_fields' => $component_reference_class->acf_schema
                ];
            }

            if(empty($component_acf_fields))
                continue;

            $this->component_setups[$post_type][] = $component_setup_list;
            
            $component_field_group_args = api_proof_acf_schema_groups([
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

    public function get_post_type_classes () {
        $override_post_type_classes = array_flip($this->options['override_post_type_classes']);
        $post_type_classes = array_flip($this->custom_post_type_classes);

        return array_flip(array_replace($post_type_classes, $override_post_type_classes));
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

    public function get_from_local_cache_stack($post_id) {
        return $this->local_cache_stack[$post_id] ?? false;
    }

    public function register_component_class ($class_name, $directory = "") {
        $this->component_classes[] = $class_name;

        if(!empty($directory))
            $this->component_locations[$class_name] = $directory;
    }

    public function register_options_class ($class_name) {
        $this->options_classes[] = $class_name;
    }

    public function register_custom_post_type_class ($class_name) {
        $this->custom_post_type_classes[api_name_to_key($class_name)] = $class_name;
    }

    static public function use_cache () {
        return FRC::get_instance()->options['use_caching'];
    }
}