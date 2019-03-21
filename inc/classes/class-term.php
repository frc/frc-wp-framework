<?php

namespace FRC;

class Term {
    public $children;
    public $permalink;
    public $acf_fields;

    public function __construct ($id) {
        $term = \get_term($id);

        $data = get_object_vars($term);

        foreach($data as $key => $value) {
            $this->$key = $value;
        }

        $this->permalink = get_term_link($id);

        if(is_wp_error($this->permalink)) {
            $this->permalink = false;
        }

        $this->acf_fields = (object) get_fields($this->taxonomy . "_" . $id);
    }

    public function get_permalink () {
        return get_term_link($this->term_id);
    }

    public function get_label () {
        return $this->name;
    }

    public function set_acf_field ($field_name, $field_value) {
        update_field($field_name, $field_value, $this->taxonomy . '_' . $this->term_id);
    }

    public function get_children () {
        $transient_key = '_frc_taxonomy_term_children_' . $this->term_id;
        if((FRC::use_cache() && ($children = get_transient($transient_key)) === false) || !FRC::use_cache()) {
            $children = [];

            foreach (get_term_children($this->term_id, $this->taxonomy) as $child_term) {
                $children[] = new Term($child_term);
            }

            set_transient($transient_key, $children);
            add_transient_to_group_list('term_' . $this->term_id, $transient_key);
            add_transient_to_group_list("terms", $transient_key);
        }
        return $children;
    }
}