<?php

class FRC_WP_Query extends WP_Query {
    public $is_from_cache = false;
    public $cache_results;
    public $expiration_time = WEEK_IN_SECONDS;
    
    public function __construct($query = [], $cache_results = true, $expiration_time = WEEK_IN_SECONDS) {
        $this->cache_results = $cache_results;
        $this->expiration_time = $expiration_time;

        parent::__construct($query);
    }

    public function get_posts () {
        if(!$this->cache_results)
            return parent::get_posts();

        $transient_key = "_frc_wp_query_" . md5(serialize($this->query));

        if(($query_result = get_transient($transient_key)) === false) {
            $wp_query_transient_list = frc_api_get_wp_query_list();

            $tmp_query_result = parent::get_posts();

            $wp_query_transient_list[] = $transient_key;

            frc_api_set_wp_query_list($wp_query_transient_list);

            $query_result = [];
            foreach($tmp_query_result as $query_post) {
                $query_result[] = frc_get_post($query_post->ID);
            }

            set_transient($transient_key, $query_result, $this->expiration_time);

            return $query_result;
        }

        $this->is_from_cache = true;
        
        return $query_result;
    }
}

add_action('save_post', function () {
    $wp_query_transient_list = frc_get_wp_query_list();

    $new_transient_list = [];
    foreach($wp_query_transient_list as $transient) {
        delete_transient($transient);

        $new_transient_list = $transient;
    }

    frc_set_wp_query_list($new_transient_list);
});