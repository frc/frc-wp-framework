<?php

namespace FRC;

class Render_Data implements \ArrayAccess {
    public function __construct ($data) {
        if(is_array($data)) {
            $this->add_array($data);
        } else if (is_object($data)) {
            foreach(get_object_vars($data) as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    public function offsetExists($key) {
        return (isset($this->$key));
    }

    public function offsetGet($key) {
        return $this->$key;
    }

    public function offsetSet($key, $value) {
        return ($this->$key = $value);
    }

    public function offsetUnset($key) {
        unset($this->$key);
    }

    public function __get ($key) {
        return $this->$key ?? null;
    }

    public function add_array ($array = []) {
        foreach ($array as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }

    public static function prepare ($data = []) {
        if(is_array($data)) {
            return new self($data);
        }

        return $data;
    }
}