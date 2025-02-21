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
        ?>
        <div class="taf-container">
            <form id="taf-search-form" class="taf-filter-form">
                <div class="taf-select-group">
                    <label for="taf-make">Gyártó:</label>
                    <select id="taf-make" class="taf-select" disabled>
                        <option value="">Válassz gyártót...</option>
                    </select>
                </div>

                <div class="taf-select-group">
                    <label for="taf-model">Modell:</label>
                    <select id="taf-model" class="taf-select" disabled>
                        <option value="">Válassz modellt...</option>
                    </select>
                </div>

                <div class="taf-select-group">
                    <label for="taf-year">Évjárat:</label>
                    <select id="taf-year" class="taf-select" disabled>
                        <option value="">Válassz évet...</option>
                    </select>
                </div>

                <button type="submit" class="taf-button">Keresés</button>
                <?php if ($is_dev_mode): ?>
                <button type="button" id="taf-all-wheels" class="taf-button taf-button-secondary">Minden felni</button>
                <?php endif; ?>
            </form>

            <div class="taf-error"></div>
            <div class="taf-loading">Betöltés...</div>
            <div class="taf-results"></div>
        </div>
        <?php
        // Puffer tartalmának visszaadása és törlése
        $output = ob_get_clean();
        return $output;
    }

    /**
     * AJAX végpontok
     */
    public function ajax_get_makes() {
        check_ajax_referer('taf-ajax-nonce', 'nonce');
        
        $makes = $this->api->get_makes();
        
        if ($makes !== false) {
            error_log('TAF Makes Response: ' . print_r($makes, true));
            wp_send_json_success($makes);
        } else {
            error_log('TAF Makes Error: Nem sikerült betölteni a gyártókat');
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
            error_log('TAF Models Response: ' . print_r($models, true));
            wp_send_json_success($models);
        } else {
            error_log('TAF Models Error: Nem sikerült betölteni a modelleket');
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
            error_log('TAF Years Response: ' . print_r($years, true));
            wp_send_json_success($years);
        } else {
            error_log('TAF Years Error: Nem sikerült betölteni az éveket');
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
        
        if (empty($make) || empty($model) || empty($year)) {
            wp_send_json_error(array(
                'message' => 'Kérlek válassz ki minden mezőt!',
                'code' => 'missing_params'
            ));
            return;
        }

        error_log('TAF Search Params - Make: ' . $make . ', Model: ' . $model . ', Year: ' . $year);
        
        $wheels = $this->api->get_wheel_specs($make, $model, $year);
        
        if (is_array($wheels)) {
            if (empty($wheels)) {
                wp_send_json_error(array(
                    'message' => 'Nem található felni a megadott paraméterekkel.',
                    'code' => 'no_results'
                ));
            } else {
                error_log('TAF Wheels Found: ' . count($wheels));
                wp_send_json_success($wheels);
            }
        } else {
            error_log('TAF Wheels Error: Nem sikerült betölteni a felni adatokat');
            wp_send_json_error(array(
                'message' => 'Nem sikerült betölteni a felni adatokat.',
                'code' => 'wheels_error'
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

        // Lekérjük az összes terméket
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        $products = wc_get_products($args);
        
        if (!empty($products)) {
            $all_wheels = array();
            foreach ($products as $product) {
                // Csak akkor adjuk hozzá, ha van készleten
                if ($product->is_in_stock()) {
                    // Árak formázása
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
                        'permalink' => $product->get_permalink(),
                        'image_url' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
                        'size' => $product->get_attribute('pa_atmero'),
                        'bolt_pattern' => $product->get_attribute('pa_osztokor')
                    );
                }
            }

            error_log('TAF All Wheels Found: ' . count($all_wheels));
            wp_send_json_success($all_wheels);
        } else {
            wp_send_json_error(array(
                'message' => 'Nem található elérhető felni.',
                'code' => 'no_wheels'
            ));
        }
    }
} 