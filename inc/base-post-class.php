<?php
class FRC_Post_Base_Class {
    public      $acf_fields;
    public      $categories;
    public      $extra_cache_data;

    public      $included_acf_fields;

    public      $cache_options = [
                    'cache_whole_object'    => true,
                    'cache_acf_fields'      => false,
                    'cache_categories'      => false
                ];

    protected   $keep_build_data    = false;
    private     $served_from_cache  = false;
    private     $post_constructed   = false;

    public function __construct ($post_id = null, $cache_options = []) {
        $this->definition();
        
        if($post_id) {
            $this->cache_options = array_replace_recursive($this->cache_options, $cache_options);

            $this->remove_unused_post_data();
            
            //Construct the real post object
            $this->construct_post_object($post_id);

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

    private function construct_post_object ($post_id) {
        $this->post_constructed = true;

        $transient_key = '_frc_api_post_object_' . $post_id;
        if(!$this->cache_options['cache_whole_object'] || ($transient_data = get_transient($transient_key)) === false) {
            $post = get_object_vars(WP_Post::get_instance($post_id));

            $this->construct_acf_fields($post_id);
            
            $this->construct_categories($post_id);

            $transient_data = ['post' => $post, 'acf_fields' => $this->acf_fields, "categories" => $this->categories];

            if($this->cache_options['cache_whole_object'])
                set_transient($transient_key, $transient_data);
        } else {
            $this->served_from_cache = true;
        }

        foreach($transient_data['post'] as $post_key => $post_value) {
            $this->$post_key = $post_value;
        }

        $this->acf_fields       = $transient_data['acf_fields'];
        $this->categories       = $transient_data['categories'];
        $this->extra_cache_data = $this->fetch_extra_cache_data($post_id);
    }

    public function construct_acf_fields ($post_id) {
        if($this->cache_options['cache_acf_fields']) {
            if(($this->acf_fields = get_transient('_frc_api_post_acf_field_' . $post_id)) === false && function_exists('get_fields')) {
                $this->acf_fields = get_fields($post_id);

                //If included acf fields is set, only include those acf fields
                if(!empty($this->included_acf_fields)) {
                    foreach($this->acf_fields as $acf_key => $acf_value) {
                        if(!in_array($acf_key, $this->included_acf_fields)) {
                            unset($this->acf_fields[$acf_key]);
                        }
                    }
                }

                set_transient('_frc_api_post_acf_field_' . $post_id, $this->acf_fields);
            }
        } else {
            $this->acf_fields = get_fields($post_id);
        }
    }

    public function construct_categories ($post_id) {
        if(($this->categories = get_transient('_frc_api_post_categories_' . $post_id)) === false) {
            foreach(get_categories($post_id) as $category) {
                $this->categories[] = get_object_vars($category);
            }

            if($this->cache_options['cache_categories']) {
                set_transient('_frc_api_post_categories_' . $post_id, $this->categories);
            }
        }
    }

    public function fetch_extra_cache_data ($post_id) {
        $data = get_transient("_frc_api_post_object_extra_data_" . $post_id);

        if(empty($data) || !is_array($data))
            $data = [];
        
        return $data;
    }

    public function update_extra_cache_data ($post_id) {
        $transient_key = "_frc_api_post_object_extra_data_" . $post_id;

        set_transient($transient_key, $this->extra_cache_data);
    }

    public function delete_extra_cache_data () {
        delete_transient("_frc_api_post_object_extra_data_" . $post_id);
    }

    public function set_extra_cache_data ($data) {
        $this->extra_cache_data = $data;

        $this->update_extra_cache_data($this->ID);
    }

    public function add_extra_cache_data ($data) {
        $this->extra_cache_data[] = $data;

        $this->update_extra_cache_data($this->ID);
    }

    public function save () {
        if(!$this->post_constructed)
            return false;

        wp_update_post($this);

        foreach($this->acf_fields as $field_key => $field_value) {
            update_field($field_key, $field_value, $this->ID);
        }

        delete_transient('_frc_api_post_object_' . $this->ID);

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
frc_exclude_class("FRC_Post");
