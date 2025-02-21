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
    }

    /**
     * Szűrő megjelenítése
     */
    public function render_filter($atts) {
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
            </form>

            <div class="taf-error"></div>
            <div class="taf-loading">Betöltés...</div>
            <div class="taf-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX végpontok
     */
    public function ajax_get_makes() {
        check_ajax_referer('taf-ajax-nonce', 'nonce');
        
        $makes = $this->api->get_makes();
        
        if ($makes) {
            wp_send_json_success($makes);
        } else {
            wp_send_json_error('Nem sikerült betölteni a gyártókat.');
        }
    }

    public function ajax_get_models() {
        check_ajax_referer('taf-ajax-nonce', 'nonce');
        
        $make = sanitize_text_field($_POST['make']);
        
        if (empty($make)) {
            wp_send_json_error('Hiányzó gyártó paraméter.');
            return;
        }

        $models = $this->api->get_models($make);
        
        if ($models) {
            wp_send_json_success($models);
        } else {
            wp_send_json_error('Nem sikerült betölteni a modelleket.');
        }
    }

    public function ajax_get_years() {
        check_ajax_referer('taf-ajax-nonce', 'nonce');
        
        $make = sanitize_text_field($_POST['make']);
        $model = sanitize_text_field($_POST['model']);
        
        if (empty($make) || empty($model)) {
            wp_send_json_error('Hiányzó paraméterek.');
            return;
        }

        $years = $this->api->get_years($make, $model);
        
        if ($years) {
            wp_send_json_success($years);
        } else {
            wp_send_json_error('Nem sikerült betölteni az éveket.');
        }
    }

    public function ajax_search_wheels() {
        check_ajax_referer('taf-ajax-nonce', 'nonce');
        
        $make = sanitize_text_field($_POST['make']);
        $model = sanitize_text_field($_POST['model']);
        $year = intval($_POST['year']);
        
        if (empty($make) || empty($model) || empty($year)) {
            wp_send_json_error('Hiányzó paraméterek.');
            return;
        }

        $wheels = $this->api->get_wheel_specs($make, $model, $year);
        
        if ($wheels) {
            wp_send_json_success($wheels);
        } else {
            wp_send_json_error('Nem sikerült betölteni a felni adatokat.');
        }
    }
} 