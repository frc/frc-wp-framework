<?php

namespace FRC;

require_once 'class-dependency-node.php';

class Dependency_Graph {
    public $to;
    public $from;

    public function __construct($post_id) {
        $this->root = Dependency_Node::create($post_id, $this);
    }

    public static function create ($post_id) {
        return new self($post_id);
    }
}