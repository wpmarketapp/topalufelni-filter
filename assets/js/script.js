jQuery(document).ready(function($) {
    let makes = [];
    let models = [];
    let years = [];

    // Cache elemek
    const $makeSelect = $('#taf-make');
    const $modelSelect = $('#taf-model');
    const $yearSelect = $('#taf-year');
    const $loading = $('.taf-loading');
    const $error = $('.taf-error');
    const $results = $('.taf-results');

    // Gyártók betöltése
    function loadMakes() {
        $loading.show();
        $error.hide();
        $makeSelect.prop('disabled', true);

        $.ajax({
            url: tafAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'taf_get_makes',
                nonce: tafAjax.nonce
            },
            success: function(response) {
                console.log('Makes response:', response);
                if (response.success && Array.isArray(response.data)) {
                    makes = response.data;
                    populateMakeSelect();
                } else {
                    const message = response.data && response.data.message 
                        ? response.data.message 
                        : 'Hiba történt a gyártók betöltése közben.';
                    showError(message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Makes error:', textStatus, errorThrown);
                showError('Hiba történt a szerverrel való kommunikáció során.');
            },
            complete: function() {
                $loading.hide();
            }
        });
    }

    // Modellek betöltése
    function loadModels(make) {
        $loading.show();
        $error.hide();
        $modelSelect.empty().append('<option value="">Válassz modellt...</option>').prop('disabled', true);
        $yearSelect.empty().append('<option value="">Válassz évet...</option>').prop('disabled', true);

        $.ajax({
            url: tafAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'taf_get_models',
                make: make,
                nonce: tafAjax.nonce
            },
            success: function(response) {
                console.log('Models response:', response);
                if (response.success && Array.isArray(response.data)) {
                    models = response.data;
                    populateModelSelect();
                } else {
                    const message = response.data && response.data.message 
                        ? response.data.message 
                        : 'Hiba történt a modellek betöltése közben.';
                    showError(message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Models error:', textStatus, errorThrown);
                showError('Hiba történt a szerverrel való kommunikáció során.');
            },
            complete: function() {
                $loading.hide();
            }
        });
    }

    // Évek betöltése
    function loadYears(make, model) {
        $loading.show();
        $error.hide();
        $yearSelect.empty().append('<option value="">Válassz évet...</option>').prop('disabled', true);

        $.ajax({
            url: tafAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'taf_get_years',
                make: make,
                model: model,
                nonce: tafAjax.nonce
            },
            success: function(response) {
                console.log('Years response:', response);
                if (response.success && Array.isArray(response.data)) {
                    years = response.data;
                    populateYearSelect();
                } else {
                    const message = response.data && response.data.message 
                        ? response.data.message 
                        : 'Hiba történt az évek betöltése közben.';
                    showError(message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Years error:', textStatus, errorThrown);
                showError('Hiba történt a szerverrel való kommunikáció során.');
            },
            complete: function() {
                $loading.hide();
            }
        });
    }

    // Select mezők feltöltése
    function populateMakeSelect() {
        $makeSelect.empty().append('<option value="">Válassz gyártót...</option>');
        if (Array.isArray(makes)) {
            makes.forEach(function(make) {
                $makeSelect.append(`<option value="${make.slug}">${make.name}</option>`);
            });
            $makeSelect.prop('disabled', false);
        } else {
            showError('Hibás formátumú gyártó adatok.');
        }
    }

    function populateModelSelect() {
        $modelSelect.empty().append('<option value="">Válassz modellt...</option>');
        if (Array.isArray(models)) {
            models.forEach(function(model) {
                $modelSelect.append(`<option value="${model.slug}">${model.name}</option>`);
            });
            $modelSelect.prop('disabled', false);
        } else {
            showError('Hibás formátumú modell adatok.');
        }
    }

    function populateYearSelect() {
        $yearSelect.empty().append('<option value="">Válassz évet...</option>');
        if (Array.isArray(years)) {
            console.log('Years data:', years); // Debug log
            years.forEach(function(year) {
                // Ellenőrizzük, hogy az év egy szám-e
                const yearValue = parseInt(year);
                if (!isNaN(yearValue)) {
                    $yearSelect.append(`<option value="${yearValue}">${yearValue}</option>`);
                }
            });
            $yearSelect.prop('disabled', false);
        } else {
            showError('Hibás formátumú év adatok.');
        }
    }

    // Hibaüzenet megjelenítése
    function showError(message) {
        console.error('TAF Error:', message);
        $error.text(message).show();
    }

    // Event listeners
    $makeSelect.on('change', function() {
        const selectedMake = $(this).val();
        if (selectedMake) {
            loadModels(selectedMake);
        } else {
            $modelSelect.empty().append('<option value="">Válassz modellt...</option>').prop('disabled', true);
            $yearSelect.empty().append('<option value="">Válassz évet...</option>').prop('disabled', true);
        }
    });

    $modelSelect.on('change', function() {
        const selectedMake = $makeSelect.val();
        const selectedModel = $(this).val();
        if (selectedMake && selectedModel) {
            loadYears(selectedMake, selectedModel);
        } else {
            $yearSelect.empty().append('<option value="">Válassz évet...</option>').prop('disabled', true);
        }
    });

    // Keresés indítása
    $('#taf-search-form').on('submit', function(e) {
        e.preventDefault();
        const make = $makeSelect.val();
        const model = $modelSelect.val();
        const year = $yearSelect.val();

        if (!make || !model || !year) {
            showError('Kérlek válassz ki minden mezőt!');
            return;
        }

        searchWheels(make, model, year);
    });

    function searchWheels(make, model, year) {
        $loading.show();
        $error.hide();
        $results.empty();

        $.ajax({
            url: tafAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'taf_search_wheels',
                make: make,
                model: model,
                year: year,
                nonce: tafAjax.nonce
            },
            success: function(response) {
                console.log('Wheels response:', response);
                if (response.success && Array.isArray(response.data)) {
                    displayResults(response.data);
                } else {
                    const message = response.data && response.data.message 
                        ? response.data.message 
                        : 'Hiba történt a keresés során.';
                    showError(message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Wheels error:', textStatus, errorThrown);
                showError('Hiba történt a szerverrel való kommunikáció során.');
            },
            complete: function() {
                $loading.hide();
            }
        });
    }

    function displayResults(wheels) {
        if (!wheels.length) {
            $results.html('<p>Nem található felni a megadott paraméterekkel.</p>');
            return;
        }

        let hasProducts = false;
        let resultsHtml = '';

        wheels.forEach(function(wheel) {
            if (wheel.matching_products && wheel.matching_products.length > 0) {
                hasProducts = true;
                wheel.matching_products.forEach(product => {
                    resultsHtml += `
                        <div class="taf-result-card">
                            <a href="${product.permalink}" class="taf-product-link">
                                ${product.image_url ? 
                                    `<div class="taf-product-image">
                                        <img src="${product.image_url}" alt="${product.name}">
                                    </div>` : 
                                    ''
                                }
                                <h3>${product.name}</h3>
                                <div class="taf-product-price">
                                    ${product.sale_price ? 
                                        `<span class="taf-sale-price">${product.sale_price} Ft</span>
                                         <span class="taf-regular-price">${product.regular_price} Ft</span>` : 
                                        `<span class="taf-price">${product.price} Ft</span>`
                                    }
                                </div>
                                <div class="taf-specs">
                                    <span>Méret: ${wheel.size}"</span>
                                    <span>Osztókör: ${wheel.bolt_pattern}</span>
                                    ${wheel.position ? `<span>Pozíció: ${wheel.position}</span>` : ''}
                                </div>
                            </a>
                        </div>
                    `;
                });
            }
        });

        if (!hasProducts) {
            $results.html('<p>Nem található elérhető felni a megadott paraméterekkel.</p>');
        } else {
            $results.html(resultsHtml);
        }

        // Debug log
        console.log('Processed wheels data:', wheels);
    }

    // Inicializálás
    loadMakes();
}); 