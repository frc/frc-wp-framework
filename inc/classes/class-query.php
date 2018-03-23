<?php
namespace FRC;

class Query extends \WP_Query {
    public $is_from_cache = false;
    public $cache_results;
    public $expiration_time = WEEK_IN_SECONDS;
    
    public function __construct($query = [], $cache_results = false, $expiration_time = WEEK_IN_SECONDS) {
        $this->cache_results = $cache_results;
        $this->expiration_time = $expiration_time;

        parent::__construct($query);
    }

    private function convert_posts_to_frc_posts ($posts) {
        return array_map(function ($post) {
            return get_post($post->ID);
        }, $posts);
    }

    public function get_posts () {
        if(!$this->cache_results) {
            return $this->convert_posts_to_frc_posts(parent::get_posts());
        }

        $transient_key = api_transient_name("_frc_wp_query_" . md5(serialize($this->query)));

        if(!FRC::use_cache() || ($query_result = get_transient($transient_key)) === false || !$this->cache_results ) {
            $frc_in_wp_query = true;
            $query_result = $this->convert_posts_to_frc_posts(parent::get_posts());
            $frc_in_wp_query = false;

            if(FRC::use_cache() && $this->cache_results) {
                $wp_query_transient_list[] = $transient_key;

                api_add_transient_to_group_list("wp_query", $transient_key);

                set_transient($transient_key, $query_result, $this->expiration_time);
            }

            return $query_result;
        }

        $this->is_from_cache = true;
        
        return $query_result;
    }
}

add_action('save_post', function () {
    $wp_query_transient_list = api_get_transient_group_list("wp_query");

    $new_transient_list = [];
    foreach($wp_query_transient_list as $transient) {
        delete_transient($transient);

        $new_transient_list = $transient;
    }

    api_set_transient_group_list("wp_query", $new_transient_list);
});