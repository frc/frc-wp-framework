<?php
namespace FRC;

/**
 * Class Post_Base_Class
 *
 */
abstract class Post_Base_Class {
    /**
     * Options that can be overridden in the child post class
     */
    public      $custom_post_type = true;
    public      $acf_schema;
    public      $acf_schema_groups;
    public      $options;
    public      $taxonomies;
    public      $args;
    public      $included_components;
    public      $default_components;
    public      $cache_options = [
                    'cache_whole_object'    => true,
                    'cache_acf_fields'      => true,
                    'cache_categories'      => true,
                    'cache_component_list'  => true,
                    'cache_components'      => false
                ];
    /**
     * Fields that contain data
     */
    public      $acf_fields;

    /**
     * Flags for helping out figuring out
     * the post object's state.
     */
    public      $served_from_cache  = false;
    protected   $keep_build_data    = false;

    public function __construct ($post_id = null, $cache_options = []) {
        $this->def();
        
        if($post_id) {
            $this->cache_options = array_replace_recursive($this->cache_options, $cache_options);

            $this->remove_unused_post_data();
            
            //Construct the real post object
            $this->construct_post_object($post_id);
            $this->construct_default_components();

            $this->init();
        }
    }

    public function remove_unused_post_data () {
        if(!$this->keep_build_data) {
            unset($this->acf_schema);
            unset($this->acf_schema_groups);
            unset($this->args);
        }
    }

    private function construct_post_object ($post_id) {
        $this->post_constructed = true;

        $transient_key = '_frc_api_post_object_' . $post_id;

        if(!FRC::use_cache() || !$this->cache_options['cache_whole_object'] || ($transient_data = get_transient($transient_key)) === false) {
            $post = get_object_vars(\WP_Post::get_instance($post_id));

            $this->construct_acf_fields($post_id);

            $transient_data = ['post' => $post, 'acf_fields' => $this->acf_fields];

            if($this->cache_options['cache_whole_object'] && FRC::use_cache()) {
                api_add_transient_to_group_list("post_" . $post_id, $transient_key);
                set_transient($transient_key, $transient_data);
            }
        } else {
            $this->served_from_cache = true;
        }

        foreach($transient_data['post'] as $post_key => $post_value) {
            $this->$post_key = $post_value;
        }

        $this->acf_fields       = $transient_data['acf_fields'];
        $this->attachments      = get_attached_media('', $this->ID);
        $this->meta_data        = $this->prepare_post_metadata($this->ID);
    }

    public function construct_acf_fields ($post_id) {
        if($this->cache_options['cache_acf_fields']) {
            $transient_key = '_frc_api_post_acf_field_' . $post_id;
            if(FRC::use_cache() || ($this->acf_fields = get_transient($transient_key)) === false && function_exists('get_fields')) {
                $this->acf_fields = (object) get_fields($post_id);

                if(FRC::use_cache()) {
                    api_add_transient_to_group_list("post_" . $post_id, $transient_key);
                    set_transient($transient_key, $this->acf_fields);
                }
            }
        } else {
            $this->acf_fields = (object) get_fields($post_id);
        }
    }

    public function construct_default_components () {
        if(empty($this->default_components))
            return;


    }

    public function prepare_post_metadata () {
        return (object) [
            'author_url' => get_author_posts_url($this->post_author),
            'author_name' => get_the_author_meta('display_name', $this->post_author)
        ];
    }

    public function get_components ($types = []) {
        if(!defined("ACF_PRO"))
            return [];

        $transient_key = '_frc_api_post_components_' . $this->ID;
        if(FRC::use_cache() || !$this->cache_options['cache_components'] || ($components = get_transient($transient_key)) === false) {
            $components = [];

            if(isset($this->acf_fields->frc_components)) {
                foreach ($this->acf_fields->frc_components as $frc_component_data) {
                    $component_class = false;

                    foreach ($this->get_included_components() as $incl_component) {
                        if ($frc_component_data['acf_fc_layout'] == api_name_to_key($incl_component)) {
                            $component_class = $incl_component;
                            break;
                        }
                    }

                    if (!$component_class)
                        continue;

                    $new_component = new $component_class();
                    $new_component->parent_post_id = $this->ID;
                    $new_component->prepare($frc_component_data);

                    $components[] = $new_component;
                }
            }

            if (FRC::use_cache()) {
                set_transient($transient_key, $components);
                api_add_transient_to_group_list("post_" . $this->ID, $transient_key);
            }
        }

        if(!empty($types)) {
            if(is_string($types)) {
                $types = [$types];
            }

            $types = array_map("strtolower", $types);

            foreach($components as $key => $component) {
                if(!in_array(strtolower(get_class($component)), $types)) {
                    unset($components[$key]);
                }
            }
        }

        return $components;
    }

    public function get_components_except ($types = []) {
        if(empty($types) || !is_array($types))
            return [];

        $types = array_map("strtolower", $types);

        $components = $this->get_components();

        foreach($components as $key => $component) {
            if(in_array(strtolower(get_class($component)), $types)) {
                unset($components[$key]);
            }
        }

        return $components;
    }

    public function get_components_with_tag ($tag) {
        $components = $this->get_components();

        $tag = strtolower($tag);

        foreach($components as $key => $component) {
            if(!in_array($tag, $component->get_tags())) {
                unset($components[$key]);
            }
        }

        return $components;
    }

    public function is_attachment () {
        return ($this->post_type == 'attachment');
    }

    public function get_s3_url () {
        global $as3cf;

        if(isset($as3cf) && $as3cf)
        var_dump($as3cf);exit;

    }

    public function get_permalink () {
        return get_the_permalink($this->ID);
    }

    public function get_archive_link () {
        return get_post_type_archive_link($this->post_type);
    }

    public function get_taxonomies () {
        return get_post_taxonomies($this->ID);
    }

    public function get_categories () {
        return $this->get_terms("category");
    }

    public function get_terms ($taxonomy = false) {
        $terms = [];

        if(!$taxonomy) {
            foreach (get_post_taxonomies($this->ID) as $taxonomy_slug) {
                foreach (wp_get_post_terms($this->ID, $taxonomy_slug, ['parent' => 0]) as $term_data) {
                    $terms[$taxonomy_slug][] = get_term($term_data->term_id);
                }
            }
        } else {
            foreach (wp_get_post_terms($this->ID, $taxonomy, ['parent' => 0]) as $term_data) {
               $terms[] = get_term($term_data->term_id);
            }
        }

        return $terms;
    }

    public function get_included_components () {
        return (isset($this->included_components) && !empty($this->included_components)) ? $this->included_components : [];
    }

    public function get_thumbnail () {
        return Attachment::from_post_thumbnail($this->ID);
    }

    public function get_key_name () {
        return api_name_to_key(get_class($this));
    }

    public function save () {
        $this->saved();
    }

    /**
     * Just some methods that are called at different times of the program.
     */
    protected function init () {}

    protected function def () {}

    protected function saved () {}
}

/*
    Create this just so that we can wrap regular wp_post's
    through the system and as this is not a custom post type,
    there is no need to put that through the registering machine.
*/
class Post extends Post_Base_Class {
    public $custom_post_type = false;
}
