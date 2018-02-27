<?php
namespace FRC;

class FRC {
    public $options;

    public $component_data_locations;
    public $component_classes;

    public $taxonomies;
    public $taxonomy_classes;
    public $post_types_to_taxonomies;

    public $custom_post_type_classes;
    public $options_classes;

    public $component_root_folders;
    public $custom_post_type_root_folders;
    public $taxonomies_root_folders;

    public $local_cache_stack;
    public $additional_classes;
    public $excluded_classes;

    public function __construct () {
        if(function_exists("acf_add_local_field_group")) {
            add_action('init', [$this, "setup_custom_taxonomies"]);
            add_action('init', [$this, "setup_post_types"]);
            add_action('init', [$this, "setup_post_type_taxonomies"]);
        }
    }

    static public function get_instance () {
        global $frc_framework_instance;

        if(!$frc_framework_instance) {
            $frc_framework_instance = new self();
        }

        return $frc_framework_instance;
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

    public function setup_custom_taxonomies () {
        if(empty($this->taxonomy_classes))
            return;

        foreach($this->taxonomy_classes as $taxonomy_class) {
            $reference_class = new $taxonomy_class();

            create_taxonomy(api_name_to_key($taxonomy_class), $reference_class->args ?? []);

            $taxonomy_acf_schema = $reference_class->acf_scheme ?? false;
            $taxonomy_acf_groups = $reference_class->acf_schema_groups ?? false;

            if($taxonomy_acf_groups) {
                \acf_add_local_field_group(api_proof_acf_schema_groups($taxonomy_acf_groups));
            } else if($taxonomy_acf_schema) {
                $field_prefix = api_name_to_key($taxonomy_class) . '_taxonomy_fields';

                $taxonomy_acf_schema = api_proof_acf_schema($taxonomy_acf_schema, $field_prefix);

                \acf_add_local_field_group(api_proof_acf_schema_groups([
                    'title'     => api_name_to_proper($taxonomy_class) . ' Fields',
                    'fields'    => $taxonomy_acf_schema,
                    'location'  => [
                        [
                            [
                                'param'     => 'taxonomy',
                                'operator'  => '==',
                                'value'     => api_name_to_key($taxonomy_class)
                            ]
                        ]
                    ]
                ]));
            }
        }
    }

    public function setup_post_types () {
        if(empty($this->custom_post_type_classes))
            return;

        foreach($this->get_post_type_classes() as $post_type_key_name => $class_name) {

            $reference_class = new $class_name();

            $post_type_proper_name = $reference_class->options['proper_name'] ?? api_name_to_proper($class_name);
            $post_type_key_name = $reference_class->options['key_name'] ?? $post_type_key_name;

            if($reference_class->custom_post_type) {

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
                $options_acf_fields = $reference_class->acf_schema ?? false;

                if($options_acf_groups) {
                    $options_acf_groups = api_proof_acf_schema_groups($options_acf_groups);

                    \acf_add_local_field_group($options_acf_groups);
                } else if ($options_acf_fields && !empty($options_acf_fields)) {
                    //Construct the acf fields
                    $field_group_key = str_replace("_", "", $post_type_key_name . '_fields');

                    $options_acf_fields = api_proof_acf_schema($options_acf_fields, $field_group_key);

                    \acf_add_local_field_group(api_proof_acf_schema_groups([
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

                //Component initializations
                if(isset($reference_class->included_components))
                    $this->register_post_type_components($post_type_key_name, $reference_class->included_components, $post_type_proper_name, $class_name);
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

    private function register_post_type_components ($post_type, $included_components, $proper_name, $class_name) {
        if(empty($included_components))
            return;

        $component_acf_fields = [];

        $current_index = 0;
        foreach($included_components as $component) {
            if(!class_exists($component)) {
                trigger_error("It seems that one of your post types (" . $post_type . "/" . $class_name . ") is trying to include a component (" . $component . "), that doesn't exist.", E_USER_NOTICE);
                continue;
            }

            $component_reference_class = new $component();

            $component_key = md5('group_' . $current_index . '_' . api_name_to_key($component));

            $component_setup_list['components'][api_name_to_key($component)] = $component;

            $component_acf_fields[$component_key] = [
                'key'        => $component_key,
                'name'       => api_name_to_key($component),
                'label'      => $component_reference_class->proper_name ?? api_name_to_proper($component),
                'sub_fields' => $component_reference_class->acf_schema
            ];

            $current_index++;
        }

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

        \acf_add_local_field_group($component_field_group_args);
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

    public function register_taxonomy_class ($class_name) {
        $this->taxonomy_classes[] = $class_name;
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