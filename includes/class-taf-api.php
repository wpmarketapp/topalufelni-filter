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
     * WooCommerce termékek keresése felni adatok alapján
     */
    private function find_matching_products($wheel_data) {
        // Ellenőrizzük, hogy a WooCommerce aktív-e
        if (!class_exists('WooCommerce')) {
            return null;
        }

        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'pa_atmero',
                    'field' => 'name',
                    'terms' => $wheel_data['size']
                )
            )
        );

        // Ha van bolt_pattern, azt is nézzük
        if (!empty($wheel_data['bolt_pattern'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'pa_osztokor',
                'field' => 'name',
                'terms' => str_replace('x', '', $wheel_data['bolt_pattern'])
            );
        }

        $products = wc_get_products($args);
        
        if (!empty($products)) {
            $matching_products = array();
            foreach ($products as $product) {
                if ($product->is_in_stock()) {
                    $matching_products[] = array(
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'price' => $product->get_price(),
                        'regular_price' => $product->get_regular_price(),
                        'sale_price' => $product->get_sale_price(),
                        'stock_quantity' => $product->get_stock_quantity(),
                        'permalink' => $product->get_permalink(),
                        'image_url' => wp_get_attachment_image_url($product->get_image_id(), 'medium')
                    );
                }
            }
            return $matching_products;
        }

        return null;
    }

    /**
     * Felni adatok lekérése
     */
    public function get_wheel_specs($make, $model, $year) {
        // Először lekérjük a modifications adatokat
        $mod_response = $this->make_request('/modifications/', array(
            'make' => $make,
            'model' => $model,
            'year' => $year
        ));

        error_log('TAF Modifications Response: ' . print_r($mod_response, true));

        if (!$mod_response || !isset($mod_response['data']) || empty($mod_response['data'])) {
            error_log('TAF Error: No modifications found');
            return array();
        }

        // Használjuk az első módosítást
        $modification = reset($mod_response['data']);

        // Most már lekérhetjük a felni adatokat a módosítással együtt
        $response = $this->make_request('/search/by_model/', array(
            'make' => $make,
            'model' => $model,
            'year' => $year,
            'modification' => $modification['slug']
        ));

        error_log('TAF Search Response: ' . print_r($response, true));

        if ($response && isset($response['data']) && !empty($response['data'])) {
            $vehicle_data = reset($response['data']);
            $wheel_sets = array();

            // Technikai adatok kinyerése
            $bolt_pattern = '';
            if (isset($vehicle_data['technical']) && isset($vehicle_data['technical']['bolt_pattern'])) {
                $bolt_pattern = $vehicle_data['technical']['bolt_pattern'];
            }

            // Kerekek feldolgozása és csoportosítása
            if (isset($vehicle_data['wheels']) && is_array($vehicle_data['wheels'])) {
                foreach ($vehicle_data['wheels'] as $wheel_set) {
                    $front_info = null;
                    $rear_info = null;

                    if (isset($wheel_set['front'])) {
                        $front = $wheel_set['front'];
                        $front_info = $this->parse_rim_info($front['rim']);
                        $front_info['tire_size'] = $front['tire'];
                    }

                    if (isset($wheel_set['rear'])) {
                        $rear = $wheel_set['rear'];
                        $rear_info = $this->parse_rim_info($rear['rim']);
                        $rear_info['tire_size'] = $rear['tire'];
                    }

                    // Kulcs generálása a felni szetthez
                    $key = '';
                    if ($front_info) {
                        $key .= $front_info['diameter'] . '_' . $front_info['width'] . '_' . $front_info['offset'];
                        // Keressük a megfelelő termékeket
                        $front_info['matching_products'] = $this->find_matching_products(array(
                            'size' => $front_info['diameter'],
                            'width' => $front_info['width'],
                            'offset' => $front_info['offset'],
                            'bolt_pattern' => $bolt_pattern
                        ));
                    }
                    if ($rear_info && $rear_info !== $front_info) {
                        $key .= '_' . $rear_info['diameter'] . '_' . $rear_info['width'] . '_' . $rear_info['offset'];
                        // Keressük a megfelelő termékeket
                        $rear_info['matching_products'] = $this->find_matching_products(array(
                            'size' => $rear_info['diameter'],
                            'width' => $rear_info['width'],
                            'offset' => $rear_info['offset'],
                            'bolt_pattern' => $bolt_pattern
                        ));
                    }

                    if (!isset($wheel_sets[$key])) {
                        $wheel_set_data = array(
                            'make' => $make,
                            'model' => $model
                        );

                        if ($front_info) {
                            $wheel_set_data['front'] = array(
                                'position' => 'Első',
                                'size' => $front_info['diameter'],
                                'width' => $front_info['width'],
                                'offset' => $front_info['offset'],
                                'bolt_pattern' => $bolt_pattern,
                                'tire_size' => $front_info['tire_size'],
                                'matching_products' => $front_info['matching_products']
                            );
                        }

                        if ($rear_info && $rear_info !== $front_info) {
                            $wheel_set_data['rear'] = array(
                                'position' => 'Hátsó',
                                'size' => $rear_info['diameter'],
                                'width' => $rear_info['width'],
                                'offset' => $rear_info['offset'],
                                'bolt_pattern' => $bolt_pattern,
                                'tire_size' => $rear_info['tire_size'],
                                'matching_products' => $rear_info['matching_products']
                            );
                        }

                        $wheel_sets[$key] = $wheel_set_data;
                    }
                }
            }

            // Átalakítjuk a wheel_sets asszociatív tömböt egyszerű tömbbé
            $wheels = array();
            foreach ($wheel_sets as $wheel_set) {
                if (isset($wheel_set['front'])) {
                    $wheels[] = $wheel_set['front'];
                }
                if (isset($wheel_set['rear'])) {
                    $wheels[] = $wheel_set['rear'];
                }
            }

            error_log('TAF Processed Wheels: ' . print_r($wheels, true));
            return $wheels;
        }

        return array();
    }

    /**
     * Felni információk feldolgozása a formátumból (pl. "9Jx19 ET45")
     */
    private function parse_rim_info($rim_string) {
        $result = array(
            'width' => '',
            'diameter' => '',
            'offset' => ''
        );

        // Példa: "9Jx19" vagy "9Jx19 ET45"
        if (preg_match('/(\d+\.?\d*)J?x(\d+)(?:\s+ET(-?\d+))?/i', $rim_string, $matches)) {
            $result['width'] = isset($matches[1]) ? $matches[1] : '';
            $result['diameter'] = isset($matches[2]) ? $matches[2] : '';
            $result['offset'] = isset($matches[3]) ? $matches[3] : '';
        }

        return $result;
    }
} 