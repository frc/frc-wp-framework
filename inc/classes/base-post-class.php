<?php
class FRC_Post_Base_Class {
    public      $acf_fields;
    public      $categories;
    public      $components;

    public      $component_setups = [
                    [
                        'types' => ['base-component']
                    ]
                ];
    public      $included_acf_fields;

    public      $options;

    public      $cache_options = [
                    'cache_whole_object'    => true,
                    'cache_acf_fields'      => true,
                    'cache_categories'      => true,
                    'cache_component_list'  => true,
                    'cache_components'      => false
                ];

    protected   $keep_build_data    = false;
    public      $served_from_cache  = false;
    private     $post_constructed   = false;

    public function __construct ($post_id = null, $cache_options = []) {
        $this->definition();
        
        if($post_id) {
            $this->cache_options = array_replace_recursive($this->cache_options, $cache_options);

            $this->remove_unused_post_data();
            
            //Construct the real post object
            $this->construct_post_object($post_id);
            $this->prepare_component_list($post_id);

            $this->init();
        }
    }

    public function remove_unused_post_data () {
        if(!$this->keep_build_data) {
            unset($this->taxonomies);
            unset($this->acf_schema);
            unset($this->acf_schema_groups);
            unset($this->args);
        }
    }

    private function prepare_component_list ($post_id) {
        if(!defined("ACF_PRO"))
            return;

        $component_setups = FRC::get_instance()->component_setups;

        $transient_key = '_frc_api_post_component_list_' . $post_id;

        if(!FRC::use_cache() || !$this->cache_options['cache_component_list'] || ($component_list = get_transient($transient_key)) === false) {
            $component_list = [];
            
            if(!empty($this->acf_fields['frc_components'])) {
                foreach($this->acf_fields['frc_components'] as $component) {
                    $acf_fc_layout = $component['acf_fc_layout'];
                    $component_class = false;

                    foreach($component_setups[$this->post_type] as $component_setup) {
                        $component_class = $component_setup['components'][$acf_fc_layout] ?? false;

                        if($component_class)
                            break;
                    }

                    if($component_class)
                        $component_list[$acf_fc_layout] = $component_class;
                }
            }
            
            if(FRC::use_cache()) {
                set_transient($transient_key, $component_list);
                frc_api_add_transient_to_group_list("post_" . $post_id, $transient_key);
            }
        }

        $this->components = $component_list;
    }

    private function construct_post_object ($post_id) {
        $this->post_constructed = true;

        $transient_key = '_frc_api_post_object_' . $post_id;

        if(!FRC::use_cache() || !$this->cache_options['cache_whole_object'] || ($transient_data = get_transient($transient_key)) === false) {
            $post = get_object_vars(WP_Post::get_instance($post_id));

            $this->construct_acf_fields($post_id);
            
            $this->construct_categories($post_id);

            $transient_data = ['post' => $post, 'acf_fields' => $this->acf_fields, "categories" => $this->categories];

            if($this->cache_options['cache_whole_object'] && FRC::use_cache()) {
                frc_api_add_transient_to_group_list("post_" . $post_id, $transient_key);
                set_transient($transient_key, $transient_data);
            }
        } else {
            $this->served_from_cache = true;
        }

        foreach($transient_data['post'] as $post_key => $post_value) {
            $this->$post_key = $post_value;
        }

        $this->acf_fields       = $transient_data['acf_fields'];
        $this->categories       = $transient_data['categories'];
    }

    public function construct_acf_fields ($post_id) {
        if($this->cache_options['cache_acf_fields']) {
            $transient_key = '_frc_api_post_acf_field_' . $post_id;
            if(FRC::use_cache() || ($this->acf_fields = get_transient($transient_key)) === false && function_exists('get_fields')) {
                $this->acf_fields = get_fields($post_id);

                //If included acf fields is set, only include those acf fields
                if(!empty($this->included_acf_fields)) {
                    foreach($this->acf_fields as $acf_key => $acf_value) {
                        if(!in_array($acf_key, $this->included_acf_fields)) {
                            unset($this->acf_fields[$acf_key]);
                        }
                    }
                }

                if(FRC::use_cache()) {
                    frc_api_add_transient_to_group_list("post_" . $post_id, $transient_key);
                    set_transient($transient_key, $this->acf_fields);
                }
            }
        } else {
            $this->acf_fields = get_fields($post_id);
        }
    }

    public function construct_categories ($post_id) {
        $transient_key = '_frc_api_post_categories_' . $post_id;
        if(FRC::use_cache() || ($this->categories = get_transient($transient_key)) === false) {
            foreach(get_categories($post_id) as $category) {
                $this->categories[] = get_object_vars($category);
            }

            if($this->cache_options['cache_categories'] && FRC::use_cache()) {
                frc_api_add_transient_to_group_list("post_" . $post_id, $transient_key);
                set_transient($transient_key, $this->categories);
            }
        }
    }

    public function get_components () {
        if(!defined("ACF_PRO"))
            return [];

        $transient_key = '_frc_api_post_components_' . $this->ID;

        if(FRC::use_cache() || !$this->cache_options['cache_components'] || ($components = get_transient($transient_key)) === false) {
            $components = [];

            if(!empty($this->acf_fields['frc_components'])) {
                foreach($this->acf_fields['frc_components'] as $component) {
                    $component_class = $this->components[$component['acf_fc_layout']] ?? null;

                    if(!isset($component_class))
                        continue;

                    $new_component = new $component_class();
                    $new_component->prepare($component);

                    $components[] = $new_component;
                }
            }

            if(FRC::use_cache()) {
                set_transient($transient_key, $components);
                frc_api_add_transient_to_group_list("post_" . $this->ID, $transient_key);
            }
        }

        return $components;
    }

    public function save () {
        if(!$this->post_constructed)
            return false;

        wp_update_post($this);

        foreach($this->acf_fields as $field_key => $field_value) {
            update_field($field_key, $field_value, $this->ID);
        }

        frc_api_delete_transients_in_group("post_" . $this->ID);

        $this->saved();

        return true;
    }

    public function get_key_name () {
        return frc_api_name_to_key(get_class($this));
    }

    protected function init () {
    }

    protected function definition () {
    }

    protected function saved () {
    }
}

/*
    Create this just so that we can wrap regular wp_post's
    through the system and as this is not a custom post type,
    there is no need to put that through the registering machine.
*/
class FRC_Post extends FRC_Post_Base_Class {
}
