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

        $this->children = $this->retrieve_children();

        $this->acf_fields = (object) get_fields($this->taxonomy . "_" . $id);
    }

    public function retrieve_children () {
        $children = [];
        foreach(get_term_children($this->term_id, $this->taxonomy) as $child_term) {
            $children[] = new Term($child_term);
        }
        return $children;
    }
}