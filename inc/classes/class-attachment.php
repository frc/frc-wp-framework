<?php

namespace FRC;

class Attachment {
    public $id;

    public $post;

    public $title;
    public $caption;
    public $description;
    public $alt;

    public $src;
    public $srcset;
    public $sizes;
    public $meta_data;

    public $url_prefix;

    public function __construct ($attachment_id) {
        $this->id = $attachment_id;
        $this->post = get_post($attachment_id);

        $this->gather_attachment_data();
    }

    public static function from_post_thumbnail ($post_id) {
        $thumbnail_id = get_post_thumbnail_id($post_id);

        return $thumbnail_id ? new self($thumbnail_id) : false;
    }

    public function gather_attachment_data () {
        $this->meta_data = (object) wp_get_attachment_metadata($this->id);

        if(!$this->meta_data)
            return false;

        $this->url_prefix = dirname(wp_get_attachment_url($this->id)) . '/';

        $this->src    = $this->meta_data->file;
        $this->sizes  = $this->gather_sizes();
        $this->srcset = $this->gather_srcset($this->id);
    }

    public function gather_sizes () {
        $url_prefix = $this->url_prefix;

        $sizes = array_map(function ($size) use ($url_prefix) {
            $size['file'] = $url_prefix . $size['file'];
            return $size;
        }, $this->meta_data->sizes);

        $sizes['full'] = [
            'width'  => $this->meta_data->width,
            'height' => $this->meta_data->height,
            'file'   => $this->meta_data->file
        ];

        return $sizes;
    }

    public function gather_info() {
        $this->title       = $this->post->post_title;
        $this->caption     = $this->post->post_excerpt;
        $this->description = $this->post->post_content;
        $this->alt         = get_post_meta($this->id, '_wp_attachment_image_alt', true);
    }

    public function gather_srcset () {

        $srcset = "";

        $c = 0;
        foreach($this->sizes as $size) {
            if($c)
                $srcset .= ', ';

            $srcset .= $size['file'] . ' ' . $size['width'] . 'w';
            $c++;
        }

        return $srcset;
    }
}