<?php
namespace FRC;

class Ajax_Post_Query extends Base_Ajax_Endpoint {
    public $endpoint_name = "post_query";

    public function get_data () {
        // Make sure the post type is ok to be reachable from rest.
        if(isset($this->params['post_type']) && !empty($this->params['post_type'])) {
            if(is_string($this->params['post_type'])) {
                $this->params['post_type'] = [$this->params['post_type']];
            }

            foreach($this->params['post_type'] as $key => $post_type) {
                $post_type_obj = get_post_type_object($post_type);

                if(!$post_type_obj)
                    continue;

                if(!isset($post_type_obj->show_in_rest) && !$post_type_obj->show_in_rest) {
                    unset($this->params['post_type'][$key]);
                }
            }
        }

        $query = new query($this->params);
        $posts = $query->get_posts();
        return $posts;
    }
}