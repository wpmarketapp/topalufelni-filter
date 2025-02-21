<?php
if (!defined('ABSPATH')) {
    exit;
}

class AFS_API {
    private $api_key;
    private $api_base_url = 'https://api.wheel-size.com/v1';
    private $cache_time;

    public function __construct() {
        $settings = get_option('afs_plugin_settings');
        $this->api_key = $settings['api_key'];
        $this->cache_time = $settings['cache_time'];
    }

    /**
     * API kérés végrehajtása
     */
    private function make_request($endpoint, $params = array()) {
        $params['user_key'] = $this->api_key;
        
        $url = add_query_arg($params, $this->api_base_url . $endpoint);
        
        $cache_key = 'afs_' . md5($url);
        $cached_response = get_transient($cache_key);
        
        if ($cached_response !== false) {
            return $cached_response;
        }

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!empty($data)) {
            set_transient($cache_key, $data, $this->cache_time);
        }

        return $data;
    }

    /**
     * Gyártók lekérése
     */
    public function get_makes() {
        return $this->make_request('/makes/');
    }

    /**
     * Modellek lekérése
     */
    public function get_models($make) {
        return $this->make_request('/models/', array(
            'make' => $make
        ));
    }

    /**
     * Évek lekérése
     */
    public function get_years($make, $model) {
        return $this->make_request('/years/', array(
            'make' => $make,
            'model' => $model
        ));
    }

    /**
     * Felni adatok lekérése
     */
    public function get_wheel_specs($make, $model, $year) {
        return $this->make_request('/search/by_model/', array(
            'make' => $make,
            'model' => $model,
            'year' => $year
        ));
    }
} 