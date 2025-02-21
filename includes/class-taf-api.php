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
        $response = $this->make_request('/generations/', array(
            'make' => $make,
            'model' => $model
        ));
        
        error_log('TAF Years Raw Response: ' . print_r($response, true));
        
        if ($response && isset($response['data'])) {
            $years = array();
            foreach ($response['data'] as $generation) {
                // Debug log
                error_log('TAF Generation Data: ' . print_r($generation, true));
                
                if (isset($generation['years'])) {
                    foreach ($generation['years'] as $year) {
                        $yearValue = intval($year);
                        if ($yearValue > 0) {
                            $years[] = $yearValue;
                        }
                    }
                }
            }
            
            // Duplikációk eltávolítása és rendezés
            $years = array_unique($years);
            rsort($years);
            
            error_log('TAF Processed Years: ' . print_r($years, true));
            
            if (empty($years)) {
                // Ha nincsenek évek a generációkban, próbáljuk meg a modifications endpointot
                $mod_response = $this->make_request('/modifications/', array(
                    'make' => $make,
                    'model' => $model
                ));
                
                if ($mod_response && isset($mod_response['data'])) {
                    foreach ($mod_response['data'] as $mod) {
                        if (isset($mod['year'])) {
                            $yearValue = intval($mod['year']);
                            if ($yearValue > 0) {
                                $years[] = $yearValue;
                            }
                        }
                    }
                    
                    $years = array_unique($years);
                    rsort($years);
                }
            }
            
            return !empty($years) ? $years : array();
        }
        return array();
    }

    /**
     * Felni adatok lekérése
     */
    public function get_wheel_specs($make, $model, $year) {
        // Először próbáljuk meg a modifications végpontot
        $mod_response = $this->make_request('/modifications/', array(
            'make' => $make,
            'model' => $model,
            'year' => $year
        ));

        error_log('TAF Modifications Response: ' . print_r($mod_response, true));

        if ($mod_response && isset($mod_response['data']) && !empty($mod_response['data'])) {
            // Használjuk az első módosítást
            $modification = reset($mod_response['data']);
            
            // Keressük a felni adatokat a módosítással
            $wheels_response = $this->make_request('/search/by_model/', array(
                'make' => $make,
                'model' => $model,
                'year' => $year,
                'modification' => $modification['slug']
            ));

            error_log('TAF Wheels Response with Modification: ' . print_r($wheels_response, true));

            if ($wheels_response && isset($wheels_response['data'])) {
                $wheels = array();
                foreach ($wheels_response['data'] as $wheel_data) {
                    if (isset($wheel_data['wheels']) && is_array($wheel_data['wheels'])) {
                        foreach ($wheel_data['wheels'] as $wheel) {
                            $wheels[] = array(
                                'make' => $make,
                                'model' => $model,
                                'size' => isset($wheel['rim_diameter']) ? $wheel['rim_diameter'] : '',
                                'width' => isset($wheel['rim_width']) ? $wheel['rim_width'] : '',
                                'offset' => isset($wheel['offset']) ? $wheel['offset'] : '',
                                'bolt_pattern' => isset($wheel['pcd']) ? $wheel['pcd'] : '',
                                'position' => isset($wheel_data['position']) ? $wheel_data['position'] : ''
                            );
                        }
                    }
                }
                return $wheels;
            }
        }

        // Ha nem sikerült a modifications-szel, próbáljuk meg a market/regions végpontot
        $regions_response = $this->make_request('/market/regions/', array());
        
        if ($regions_response && isset($regions_response['data']) && !empty($regions_response['data'])) {
            // Használjuk az első régiót (általában EU)
            $region = reset($regions_response['data']);
            
            $wheels_response = $this->make_request('/search/by_model/', array(
                'make' => $make,
                'model' => $model,
                'year' => $year,
                'region' => $region['slug']
            ));

            error_log('TAF Wheels Response with Region: ' . print_r($wheels_response, true));

            if ($wheels_response && isset($wheels_response['data'])) {
                $wheels = array();
                foreach ($wheels_response['data'] as $wheel_data) {
                    if (isset($wheel_data['wheels']) && is_array($wheel_data['wheels'])) {
                        foreach ($wheel_data['wheels'] as $wheel) {
                            $wheels[] = array(
                                'make' => $make,
                                'model' => $model,
                                'size' => isset($wheel['rim_diameter']) ? $wheel['rim_diameter'] : '',
                                'width' => isset($wheel['rim_width']) ? $wheel['rim_width'] : '',
                                'offset' => isset($wheel['offset']) ? $wheel['offset'] : '',
                                'bolt_pattern' => isset($wheel['pcd']) ? $wheel['pcd'] : '',
                                'position' => isset($wheel_data['position']) ? $wheel_data['position'] : ''
                            );
                        }
                    }
                }
                return $wheels;
            }
        }

        return array();
    }
} 