<?php
if (!defined('ABSPATH')) {
    exit;
}

class TAF_Shortcode {
    private $api;

    public function __construct($api) {
        $this->api = $api;
        add_shortcode('topalufelni_filter', array($this, 'render_filter'));
        add_action('wp_ajax_taf_get_makes', array($this, 'ajax_get_makes'));
        add_action('wp_ajax_nopriv_taf_get_makes', array($this, 'ajax_get_makes'));
        add_action('wp_ajax_taf_get_models', array($this, 'ajax_get_models'));
        add_action('wp_ajax_nopriv_taf_get_models', array($this, 'ajax_get_models'));
        add_action('wp_ajax_taf_get_years', array($this, 'ajax_get_years'));
        add_action('wp_ajax_nopriv_taf_get_years', array($this, 'ajax_get_years'));
        add_action('wp_ajax_taf_search_wheels', array($this, 'ajax_search_wheels'));
        add_action('wp_ajax_nopriv_taf_search_wheels', array($this, 'ajax_search_wheels'));
        add_action('wp_ajax_taf_get_all_wheels', array($this, 'ajax_get_all_wheels'));
        add_action('wp_ajax_nopriv_taf_get_all_wheels', array($this, 'ajax_get_all_wheels'));
    }

    /**
     * Szűrő megjelenítése
     */
    public function render_filter($atts) {
        // Ellenőrizzük a developer módot
        $settings = get_option('taf_plugin_settings', array(
            'dev_mode' => false
        ));
        $is_dev_mode = isset($settings['dev_mode']) && $settings['dev_mode'];

        // Kimeneti pufferelés indítása
        ob_start();
        try {
            ?>
            <div class="taf-container taf-unique-container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
                <form id="taf-search-form" class="taf-filter-form taf-unique-form" style="background: #080a0b; padding: 20px; border-radius: 8px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                    <div class="taf-select-group taf-unique-group" style="flex: 1; min-width: 200px; margin: 0;">
                        <label for="taf-make" style="display: block; margin-bottom: 5px; font-weight: 600; color: #fff;">Gyártó:</label>
                        <select id="taf-make" class="taf-select taf-unique-select" disabled style="display: block; width: 100%; height: 40px; padding: 8px 24px 8px 12px; color: #fff; background-color: #333; border: 1px solid #444; border-radius: 4px;">
                            <option value="">Válassz gyártót...</option>
                        </select>
                    </div>

                    <div class="taf-select-group taf-unique-group" style="flex: 1; min-width: 200px; margin: 0;">
                        <label for="taf-model" style="display: block; margin-bottom: 5px; font-weight: 600; color: #fff;">Modell:</label>
                        <select id="taf-model" class="taf-select taf-unique-select" disabled style="display: block; width: 100%; height: 40px; padding: 8px 24px 8px 12px; color: #fff; background-color: #333; border: 1px solid #444; border-radius: 4px;">
                            <option value="">Válassz modellt...</option>
                        </select>
                    </div>

                    <div class="taf-select-group taf-unique-group" style="flex: 1; min-width: 200px; margin: 0;">
                        <label for="taf-year" style="display: block; margin-bottom: 5px; font-weight: 600; color: #fff;">Évjárat:</label>
                        <select id="taf-year" class="taf-select taf-unique-select" disabled style="display: block; width: 100%; height: 40px; padding: 8px 24px 8px 12px; color: #fff; background-color: #333; border: 1px solid #444; border-radius: 4px;">
                            <option value="">Válassz évet...</option>
                        </select>
                    </div>

                    <button type="submit" class="taf-button taf-unique-button" style="display: block; min-width: 150px; padding: 12px 20px; color: #000; background-color: #29f60c; border: none; border-radius: 4px; cursor: pointer;">Keresés</button>
                    <?php if ($is_dev_mode): ?>
                    <button type="button" id="taf-all-wheels" class="taf-button taf-button-secondary taf-unique-button" style="background-color: #444; color: #fff; margin-left: 10px;">Developer Test</button>
                    <?php endif; ?>
                </form>

                <div class="taf-error taf-unique-error"></div>
                <div class="taf-results taf-unique-results"></div>
            </div>
            <?php
            // Puffer tartalmának lekérése és törlése
            $output = ob_get_clean();
            return $output;
        } catch (Exception $e) {
            // Hiba esetén töröljük a puffert és naplózzuk a hibát
            ob_end_clean();
            error_log('TAF Error in render_filter: ' . $e->getMessage());
            return '<div class="taf-error">Hiba történt a szűrő betöltése közben.</div>';
        }
    }

    /**
     * AJAX végpontok
     */
    public function ajax_get_makes() {
        check_ajax_referer('taf-ajax-nonce', 'nonce');
        
        $makes = $this->api->get_makes();
        
        if ($makes !== false) {
            wp_send_json_success($makes);
        } else {
            error_log('TAF Error: Nem sikerült betölteni a gyártókat');
            wp_send_json_error(array(
                'message' => 'Nem sikerült betölteni a gyártókat.',
                'code' => 'makes_error'
            ));
        }
    }

    public function ajax_get_models() {
        check_ajax_referer('taf-ajax-nonce', 'nonce');
        
        $make = sanitize_text_field($_POST['make']);
        
        if (empty($make)) {
            wp_send_json_error(array(
                'message' => 'Hiányzó gyártó paraméter.',
                'code' => 'missing_make'
            ));
            return;
        }

        $models = $this->api->get_models($make);
        
        if ($models !== false) {
            wp_send_json_success($models);
        } else {
            error_log('TAF Error: Nem sikerült betölteni a modelleket');
            wp_send_json_error(array(
                'message' => 'Nem sikerült betölteni a modelleket.',
                'code' => 'models_error'
            ));
        }
    }

    public function ajax_get_years() {
        check_ajax_referer('taf-ajax-nonce', 'nonce');
        
        $make = sanitize_text_field($_POST['make']);
        $model = sanitize_text_field($_POST['model']);
        
        if (empty($make) || empty($model)) {
            wp_send_json_error(array(
                'message' => 'Hiányzó paraméterek.',
                'code' => 'missing_params'
            ));
            return;
        }

        $years = $this->api->get_years($make, $model);
        
        if ($years !== false) {
            wp_send_json_success($years);
        } else {
            error_log('TAF Error: Nem sikerült betölteni az éveket');
            wp_send_json_error(array(
                'message' => 'Nem sikerült betölteni az éveket.',
                'code' => 'years_error'
            ));
        }
    }

    public function ajax_search_wheels() {
        check_ajax_referer('taf-ajax-nonce', 'nonce');
        
        $make = sanitize_text_field($_POST['make']);
        $model = sanitize_text_field($_POST['model']);
        $year = intval($_POST['year']);
        
        error_log('TAF Debug - Keresési paraméterek: ' . print_r(array(
            'make' => $make,
            'model' => $model,
            'year' => $year
        ), true));

        if (empty($make) || empty($model) || empty($year)) {
            wp_send_json_error(array(
                'message' => 'Kérlek válassz ki minden mezőt!',
                'code' => 'missing_params'
            ));
            return;
        }

        // Lekérjük a felni specifikációkat
        $response = $this->api->get_wheel_specs($make, $model, $year);
        error_log('TAF Debug - API válasz: ' . print_r($response, true));

        // Ha hiba történt vagy nincs adat
        if (isset($response['error']) || empty($response['wheel_sets'])) {
            error_log('TAF Debug - Nincs találat vagy hiba: ' . print_r($response, true));
            wp_send_json_error(array(
                'message' => 'Nem található felni a megadott paraméterekkel.',
                'code' => 'no_results'
            ));
            return;
        }

        // Csak a méreteket gyűjtjük ki
        $needed_sizes = array();
        foreach ($response['wheel_sets'] as $set) {
            if (isset($set['front'])) {
                $size = preg_replace('/[^0-9]/', '', $set['front']['diameter']);
                if (!empty($size)) {
                    $needed_sizes[] = $size;
                }
            }
            if (isset($set['rear'])) {
                $size = preg_replace('/[^0-9]/', '', $set['rear']['diameter']);
                if (!empty($size)) {
                    $needed_sizes[] = $size;
                }
            }
        }
        $needed_sizes = array_unique($needed_sizes);
        sort($needed_sizes);

        error_log('TAF Debug - Szükséges méretek: ' . print_r($needed_sizes, true));

        // Ha nincs méret, nincs mit keresni
        if (empty($needed_sizes)) {
            error_log('TAF Debug - Nem található méret');
            wp_send_json_error(array(
                'message' => 'Nem található felni méret a megadott paraméterekkel.',
                'code' => 'no_sizes'
            ));
            return;
        }

        // Lekérjük az összes elérhető méretet
        $available_terms = get_terms(array(
            'taxonomy' => 'pa_atmero',
            'hide_empty' => true
        ));

        $available_sizes = array();
        if (!is_wp_error($available_terms)) {
            foreach ($available_terms as $term) {
                $size = preg_replace('/[^0-9]/', '', $term->name);
                if (!empty($size)) {
                    $available_sizes[] = $size;
                }
            }
            sort($available_sizes);
        }
        error_log('TAF Debug - Elérhető méretek: ' . print_r($available_sizes, true));

        // Lekérjük a termékeket a megfelelő méretekkel
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'orderby' => 'title',
            'order' => 'ASC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'pa_atmero',
                    'field' => 'name',
                    'terms' => $needed_sizes,
                    'operator' => 'IN'
                )
            )
        );

        error_log('TAF Debug - WP_Query argumentumok: ' . print_r($args, true));

        $query = new WP_Query($args);
        error_log('TAF Debug - Találatok száma: ' . $query->post_count);

        $matching_wheels = array();

        if ($query->have_posts()) {
            $product_ids = wp_list_pluck($query->posts, 'ID');
            error_log('TAF Debug - Talált termék ID-k: ' . print_r($product_ids, true));
            
            $products = array_map('wc_get_product', $product_ids);
            $products = array_filter($products);

            foreach ($products as $product) {
                if ($product->is_in_stock()) {
                    $product_size = preg_replace('/[^0-9]/', '', $product->get_attribute('pa_atmero'));
                    error_log('TAF Debug - Termék ellenőrzése - ID: ' . $product->get_id() . ', Méret: ' . $product_size);
                    
                    if (in_array($product_size, $needed_sizes)) {
                        error_log('TAF Debug - Megfelelő termék találat - ID: ' . $product->get_id());
                        $regular_price = $product->get_regular_price();
                        $sale_price = $product->get_sale_price();
                        $price = $product->get_price();

                        $matching_wheels[] = array(
                            'id' => $product->get_id(),
                            'name' => $product->get_name(),
                            'price' => number_format($price, 0, ',', ' '),
                            'regular_price' => $regular_price ? number_format($regular_price, 0, ',', ' ') : '',
                            'sale_price' => $sale_price ? number_format($sale_price, 0, ',', ' ') : '',
                            'permalink' => $product->get_permalink(),
                            'image_url' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
                            'size' => $product_size,
                            'bolt_pattern' => $product->get_attribute('pa_osztokor')
                        );

                        if (count($matching_wheels) >= 10) {
                            break;
                        }
                    } else {
                        error_log('TAF Debug - Nem megfelelő méret - Termék ID: ' . $product->get_id() . ', Méret: ' . $product_size);
                    }
                } else {
                    error_log('TAF Debug - Nincs készleten - Termék ID: ' . $product->get_id());
                }
            }
        }

        error_log('TAF Debug - Megfelelő termékek száma: ' . count($matching_wheels));

        if (!empty($matching_wheels)) {
            wp_send_json_success($matching_wheels);
        } else {
            $error_message = 'Nem található elérhető felni a megadott paraméterekkel.<br><br>';
            $error_message .= 'API által visszaadott méretek: ' . implode(', ', array_map(function($size) { 
                return $size . '"'; 
            }, $needed_sizes)) . '<br>';
            $error_message .= 'Elérhető méretek: ' . implode(', ', array_map(function($size) {
                return $size . '"';
            }, $available_sizes));

            error_log('TAF Debug - Hibaüzenet: ' . $error_message);

            wp_send_json_error(array(
                'message' => $error_message,
                'code' => 'no_matching_wheels'
            ));
        }
    }

    /**
     * Összes felni lekérése
     */
    public function ajax_get_all_wheels() {
        check_ajax_referer('taf-ajax-nonce', 'nonce');
        
        // Ellenőrizzük a developer módot
        $settings = get_option('taf_plugin_settings');
        if (!isset($settings['dev_mode']) || !$settings['dev_mode']) {
            wp_send_json_error(array(
                'message' => 'Developer mód nincs bekapcsolva.',
                'code' => 'dev_mode_disabled'
            ));
            return;
        }

        // Ellenőrizzük, hogy a WooCommerce aktív-e
        if (!class_exists('WooCommerce')) {
            error_log('TAF Error: WooCommerce nincs aktiválva');
            wp_send_json_error(array(
                'message' => 'WooCommerce nincs aktiválva.',
                'code' => 'woocommerce_inactive'
            ));
            return;
        }

        // Lekérjük az összes terméket
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $product_ids = wp_list_pluck($query->posts, 'ID');
            $products = array_map('wc_get_product', $product_ids);
            $products = array_filter($products);

            $all_wheels = array();
            foreach ($products as $product) {
                if ($product->is_in_stock()) {
                    $regular_price = $product->get_regular_price();
                    $sale_price = $product->get_sale_price();
                    $price = $product->get_price();

                    $all_wheels[] = array(
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'price' => number_format($price, 0, ',', ' '),
                        'regular_price' => $regular_price ? number_format($regular_price, 0, ',', ' ') : '',
                        'sale_price' => $sale_price ? number_format($sale_price, 0, ',', ' ') : '',
                        'stock_quantity' => $product->get_stock_quantity(),
                        'stock_status' => $product->get_stock_status(),
                        'permalink' => $product->get_permalink(),
                        'image_url' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
                        'size' => $product->get_attribute('pa_atmero'),
                        'bolt_pattern' => $product->get_attribute('pa_osztokor')
                    );

                    // Ha már 10 terméket összegyűjtöttünk, kilépünk a ciklusból
                    if (count($all_wheels) >= 10) {
                        break;
                    }
                }
            }

            if (!empty($all_wheels)) {
                wp_send_json_success($all_wheels);
            } else {
                wp_send_json_error(array(
                    'message' => 'Nem található készleten lévő felni.',
                    'code' => 'no_stock'
                ));
            }
        } else {
            error_log('TAF Error: Nem található termék');
            wp_send_json_error(array(
                'message' => 'Nem található elérhető felni.',
                'code' => 'no_wheels'
            ));
        }
    }
} 