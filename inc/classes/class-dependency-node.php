<?php

namespace FRC;

class Dependency_Node {
    public $post_id;
    public $graph;
    public $nodes = [];

    public function __construct($post_id, $graph) {
        $graph->all_nodes[$post_id] = $this;
        $this->post_id = $post_id;
        $this->graph = $graph;
        $dependency_data = get_transient('frc_post_dependency_data_' . $post_id);

        $dependency_data = ($dependency_data) ? $dependency_data : false;

        if(!$dependency_data) {
            $dependency_data = $this->construct_data();
        }

        $this->nodes = array_map(function ($post_id) use ($graph) {
            return Dependency_Node::create($post_id, $graph);
        }, $dependency_data);

    }

    public function clear_data () {
        delete_transient('frc_post_dependency_data_' . $this->post_id);

        foreach($this->nodes as $node) {
            $node->clear_data();
        }
    }

    private function construct_data () {

        $post_obj = get_post($this->post_id);

        $posts = $this->fetch_post_data($post_obj->acf_fields);
        $post_ids = [];
        foreach($posts as $post) {
            $post_ids[] = $post->ID;
        }

        set_transient('frc_post_dependency_data_' . $this->post_id, $post_ids);

        return $post_ids;
    }

    private function fetch_post_data ($data) {
        $returned_posts = [];

        if(is_object($data) && $data instanceof \WP_Post) {
            return [$data];
        } if(is_array($data) || is_object($data)) {
            foreach((array) $data as $key => $value) {
                $returned_posts = array_merge($returned_posts, $this->fetch_post_data($value));
            }
        }

        return $returned_posts;
    }

    public static function create ($post_id, $graph) {
        return $graph->all_nodes[$post_id] ?? new Dependency_Node($post_id, $graph);
    }
}