<?php
namespace FRC;

class FRC {
    public $options;

    public $component_data_locations;

    public $taxonomies;
    public $post_type_taxonomies;
    public $post_type_default_components;

    public $component_classes;
    public $taxonomy_classes;
    public $custom_post_type_classes;
    public $options_classes;

    public $root_folders;

    public $additional_classes;
    public $excluded_classes;

    public $acf_schema_register_queue;

    public function __construct () {
        if(function_exists("acf_add_local_field_group")) {
            add_action('init', [$this, "setup_all"]);

            add_action('save_post', [$this, "admin_save_post"]);

            if (is_admin()) {
                add_action('init', [$this, "admin_setup_post_type_default_components"]);
            }
        }
    }

    static public function get_instance () {
        global $frc_framework_instance;

        if(!$frc_framework_instance) {
            $frc_framework_instance = new self();
        }

        return $frc_framework_instance;
    }

    static public function boot () {
        self::get_instance();
    }

    public function setup_all () {
        $this->setup_configurations();
        $this->setup_custom_taxonomies();
        $this->setup_post_types();
        $this->setup_post_type_taxonomies();
        $this->setup_options();
        $this->setup_content_type_acf_schemas();
    }

    public function admin_setup_post_type_default_components () {
        add_filter('acf/load_value/name=' . FRC_COMPONENTS_KEY, function ($value, $post_id, $field) {
            if(get_post_status($post_id) != 'auto-draft')
                return $value;

            $post_type = get_post_type($post_id);

            if(!isset($this->post_type_default_components[$post_type])) {
                return $value;
            }

            foreach($this->post_type_default_components[$post_type] as $component) {
                $value[] = [
                    'acf_fc_layout' => $component
                ];
            }

            return $value;
        }, 10, 3);
    }

    public function admin_save_post ($post_id) {
        $post = get_post($post_id);
        $post->save();

        $this->admin_save_post_components($post_id);
    }

    function admin_save_post_components ($post_id) {
        $components = get_post($post_id)->get_components();

        if(empty($components))
           return;

        foreach($components as $component) {
            $component->pre_save();
        }

        foreach($components as $component) {
            $component->save();
        }
    }

    public function setup_configurations () {
        register_folders();
        set_options();
    }

    public function setup_custom_taxonomies () {
        if(empty($this->taxonomy_classes))
            return;

        $frc_framework = FRC::get_instance();

        foreach($this->taxonomy_classes as $taxonomy_class) {
            $reference_class = new $taxonomy_class();

            $taxonomy_options = $reference_class->options ?? [];
            $taxonomy_key     = $taxonomy_options['key_name'] ?? api_name_to_key($taxonomy_class);
            $taxonomy_proper  = $taxonomy_options['proper_name'] ?? api_name_to_proper($taxonomy_class);

            $default_custom_taxonomy_args = [
                'labels' => [
                    'name'          => $taxonomy_proper,
                    'singular_name' => $taxonomy_proper
                ],
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'hierarchical'      => true,
                'rewrite'           => [
                    'slug' => $taxonomy_key
                ]
            ];

            $taxonomy_args =  array_replace_recursive($default_custom_taxonomy_args, $reference_class->args ?? []);

            $frc_framework->taxonomies[$taxonomy_key] = $taxonomy_args;

            $this->acf_schema_register_queue[] = [
                'type'        => 'taxonomy',
                'class'       => $reference_class,
                'key_name'    => $taxonomy_key,
                'proper_name' => $taxonomy_proper
            ];
        }
    }

    public function setup_post_type_taxonomies () {
        if(!empty($this->post_type_taxonomies)) {
            foreach ($this->taxonomies as $taxonomy_name => $taxonomy) {

                if(!isset($this->post_type_taxonomies['taxonomies'][$taxonomy_name]))
                    continue;

                $taxonomy_post_types = $this->post_type_taxonomies['taxonomies'][$taxonomy_name];

                if(empty($taxonomy_post_types))
                    continue;

                register_taxonomy($taxonomy_name, $taxonomy_post_types, $taxonomy);
            }
        }
    }

    public function setup_post_types () {
        if(empty($this->custom_post_type_classes))
            return;

        $register_post_type_fields_queue = [];

        /*
         * Register the post types themselves
         */
        foreach($this->get_post_type_classes() as $post_type_key_name => $class_name) {

            $reference_class = new $class_name();

            $post_type_options = $reference_class->options ?? [];

            $post_type_proper_name  = $post_type_options['proper_name'] ?? api_name_to_proper($class_name);
            $post_type_description  = $post_type_options['description'] ?? 'Automatically generated post type';
            $overwrite_args         = $reference_class->overwrite_args ?? false;

            if(isset($reference_class->custom_post_type) && $reference_class->custom_post_type) {
                $post_type_key_name = $reference_class->get_key_name();
            }

            $post_type_taxonomies = $reference_class->taxonomies ?? [];

            if(!empty($post_type_taxonomies)) {
                $post_type_taxonomies = array_map('FRC\api_name_to_key', $post_type_taxonomies);

                $this->post_type_taxonomies['post_types'][$post_type_key_name] = $post_type_taxonomies;

                foreach ($post_type_taxonomies as $post_type_taxonomy) {
                    $this->post_type_taxonomies['taxonomies'][$post_type_taxonomy][] = $post_type_key_name;
                }
            }

            if(isset($reference_class->custom_post_type) && $reference_class->custom_post_type) {

                $default_register_post_type_args = [
                    'name' => $post_type_proper_name,
                    'description' => $post_type_proper_name,
                    'labels' => [
                        'name' => $post_type_proper_name,
                        'singular_name' => $post_type_proper_name,
                        'menu_name' => $post_type_proper_name
                    ],
                    'public' => true,
                    'publicly_queryable' => true,
                    'show_ui' => true,
                    'show_in_menu' => true,
                    'show_in_admin_bar' => true,
                    'show_in_nav_menus' => true,
                    'query_var' => true,
                    'rewrite' => array('slug' => $post_type_key_name),
                    'capability_type' => 'page',
                    'has_archive' => true,
                    'can_export' => true,
                    'hierarchical' => false,
                    'menu_position' => 5,
                    'show_in_rest' => true,
                    'taxonomies' => $post_type_taxonomies,
                ];

                $options_args = $reference_class->args ?? [];

                if(!$overwrite_args) {
                    $register_post_type_args = array_replace_recursive($default_register_post_type_args, $options_args);
                } else {
                    $register_post_type_args = array_merge_recursive($default_register_post_type_args, $options_args);
                }

                register_post_type($post_type_key_name, $register_post_type_args);
            }

            $this->acf_schema_register_queue[] = [
                'type'        => 'post_type',
                'class'       => $reference_class,
                'key_name'    => $post_type_key_name,
                'proper_name' => $post_type_proper_name
            ];
        }
    }

    public function setup_content_type_acf_schemas () {
        /*
         * Register post type components and acf fields
         */
        foreach($this->acf_schema_register_queue as $register_queue_data) {
            $post_type_key_name     = $register_queue_data['key_name'];
            $reference_class        = $register_queue_data['class'];
            $post_type_proper_name  = $register_queue_data['proper_name'];
            $class_name             = get_class($reference_class);
            $type                   = $register_queue_data['type'];

            if(function_exists('acf_add_local_field_group')) {
                $options_acf_groups = $reference_class->acf_schema_groups ?? false;
                $options_acf_fields = $reference_class->acf_schema ?? false;

                if($options_acf_groups) {
                    $options_acf_groups = api_proof_acf_schema_groups($options_acf_groups);

                    \acf_add_local_field_group($options_acf_groups);
                } else if ($options_acf_fields && !empty($options_acf_fields)) {
                    //Construct the acf fields
                    $field_group_key = $reference_class->get_key_group_key_name();

                    $options_acf_fields = api_proof_acf_schema($options_acf_fields, $field_group_key);

                    $this->item_error_printing(api_validate_acf_schema($options_acf_fields), $reference_class);

                    \acf_add_local_field_group(api_proof_acf_schema_groups([
                        'title'     => $reference_class->options['acf_group_name'] ?? $post_type_proper_name . ' Fields',
                        'fields'    => $options_acf_fields,
                        'location'  => [
                            [
                                [
                                    'param'     => $type,
                                    'operator'  => '==',
                                    'value'     => $post_type_key_name,
                                ]
                            ]
                        ]
                    ]));
                }

                //Component initializations
                if(isset($reference_class->included_components) && $type == 'post_type') {

                    $this->register_post_type_components($post_type_key_name, $reference_class->included_components, $post_type_proper_name, $class_name);

                    if(isset($reference_class->default_components) && !empty($reference_class->default_components)) {
                        foreach($reference_class->default_components as $component) {
                            $this->post_type_default_components[$post_type_key_name][] = api_name_to_key($component);
                        }
                    }
                }
            }
        }
    }

    private function item_error_printing ($errors, $reference_class) {
        if(!$errors) {
            return;
        }

        $reflection = (new \ReflectionClass($reference_class));
        $filename = $reflection->getFileName();
        $class_name = $reflection->getName();

        foreach($errors as $error) {
            trigger_error($error[0], E_USER_NOTICE);

        }

        trigger_error("Errors in the definition of " . $class_name . ".", E_USER_ERROR);
    }

    private function register_post_type_components ($post_type, $included_components, $proper_name, $class_name) {
        if(empty($included_components))
            return;

        $component_acf_fields = [];

        foreach($included_components as $component) {
            if(!class_exists($component)) {
                trigger_error("It seems that one of your post types (" . $post_type . "/" . $class_name . ") is trying to include a component (" . $component . "), that doesn't exist.", E_USER_NOTICE);
                continue;
            }

            $component_reference_class = new $component();

            $component_args         = $component_reference_class->args ?? [];
            $component_options      = $component_reference_class->options ?? [];
            $component_proper_name  = $component_options['proper_name'] ?? api_name_to_proper($component);
            $component_key          = md5('group_' . api_name_to_key($post_type) . '_' . api_name_to_key($component));

            $component_setup_list['components'][api_name_to_key($component)] = $component;

            $component_acf_fields[$component_key] = array_replace_recursive([
                'key'        => $component_key,
                'name'       => api_name_to_key($component),
                'label'      => $component_proper_name,
                'sub_fields' => $component_reference_class->acf_schema
            ], $component_args);
        }

        $component_field_group_args = api_proof_acf_schema_groups([
            'title'  => $proper_name . ' Components',
            'fields' => [
                [
                    'label'         => 'Components',
                    'name'          => FRC_COMPONENTS_KEY,
                    'type'          => 'flexible_content',
                    'layouts'       => $component_acf_fields,
                    'button_label'  => 'Add component'
                ]
            ],
            'style' => 'seamless',
            'location' => [
                [
                    [
                        'param'     => 'post_type',
                        'operator'  => '==',
                        'value'     => $post_type,
                    ]
                ]
            ]
        ], api_name_to_key($post_type) . '_components_group_');

        $component_field_group_args = array_replace_recursive($component_field_group_args, $component_setup['acf_group_schema'] ?? []);

        \acf_add_local_field_group($component_field_group_args);
    }

    public function setup_options () {
        foreach($this->options_classes ?? [] as $options_class) {
            $options_reference_class = new $options_class();

            $options_acf_schema         = $options_reference_class->acf_schema ?? [];
            $options_acf_schema_groups  = $options_reference_class->acf_schema_groups ?? [];
            $options_args               = $options_reference_class->args ?? [];
            $options_parent_menu        = strtolower($options_reference_class->parent_menu ?? '');

            $options_slug = 'frc_' . api_name_to_key($options_class);

            if($options_parent_menu) {
                \acf_add_options_sub_page(array_replace_recursive([
                    'page_title' => api_name_to_proper($options_class),
                    'menu_title' => api_name_to_proper($options_class),
                    'menu_slug'  => $options_slug,
                    'parent_slug' => $options_parent_menu
                ]), $options_args);
            } else {
                \acf_add_options_page(array_replace_recursive([
                    'page_title' => api_name_to_proper($options_class),
                    'menu_title' => api_name_to_proper($options_class),
                    'menu_slug' => $options_slug
                ], $options_args));
            }

            $proofed_schema_groups = api_proof_acf_schema_groups(array_replace_recursive([
                'title' => api_name_to_proper($options_class) . ' Options',
                'fields' => $options_acf_schema,
                'style' => 'seamless',
                'location' => [
                    [
                        [
                            'param'     => 'options_page',
                            'operator'  => '==',
                            'value'     => $options_slug
                        ]
                    ]
                ]
            ], $options_acf_schema_groups));

            \acf_add_local_field_group($proofed_schema_groups);
        }
    }

    public function get_post_type_classes () {
        $override_post_type_classes = array_flip($this->options['override_post_type_classes'] ?? []);
        $post_type_classes = array_flip($this->custom_post_type_classes ?? []);

        return array_flip(array_replace($post_type_classes, $override_post_type_classes));
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
