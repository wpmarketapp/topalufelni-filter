<?php
if (!defined('ABSPATH')) {
    exit;
}

class TAF_API {
    private $api_key;
    private $api_base_url = 'https://api.wheel-size.com/v2';
    private $cache_time;

    public function __construct() {
        $settings = get_option('taf_plugin_settings');
        $this->api_key = $settings['api_key'];
        $this->cache_time = $settings['cache_time'];
    }

    /**
     * API kérés végrehajtása
     */
    private function make_request($endpoint, $params = array()) {
        $params['user_key'] = $this->api_key;
        
        $url = add_query_arg($params, $this->api_base_url . $endpoint);
        
        $cache_key = 'taf_' . md5($url);
        $cached_response = get_transient($cache_key);
        
        if ($cached_response !== false) {
            return $cached_response;
        }

        error_log('TAF API Request URL: ' . $url);

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            error_log('TAF API Error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('TAF API HTTP Error: ' . $response_code);
            error_log('TAF API Response: ' . wp_remote_retrieve_body($response));
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('TAF API JSON Error: ' . json_last_error_msg());
            error_log('TAF API Raw Response: ' . $body);
            return false;
        }

        if (!empty($data)) {
            set_transient($cache_key, $data, $this->cache_time);
        } else {
            error_log('TAF API Empty Response Data');
        }

        return $data;
    }

    /**
     * Gyártók lekérése
     */
    public function get_makes() {
        $response = $this->make_request('/makes/');
        if ($response && isset($response['data'])) {
            return $response['data'];
        }
        return false;
    }

    /**
     * Modellek lekérése
     */
    public function get_models($make) {
        $response = $this->make_request('/models/', array(
            'make' => $make
        ));
        if ($response && isset($response['data'])) {
            return $response['data'];
        }
        return false;
    }

    /**
     * Évek lekérése
     */
    public function get_years($make, $model) {
        $response = $this->make_request('/years/', array(
            'make' => $make,
            'model' => $model
        ));
        if ($response && isset($response['data'])) {
            return $response['data'];
        }
        return false;
    }

    /**
     * Felni adatok lekérése
     */
    public function get_wheel_specs($make, $model, $year) {
        $response = $this->make_request('/search/by_model/', array(
            'make' => $make,
            'model' => $model,
            'year' => $year
        ));
        if ($response && isset($response['data'])) {
            return $response['data'];
        }
        return false;
    }
} 