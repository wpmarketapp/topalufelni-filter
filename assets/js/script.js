jQuery(document).ready(function($) {
    let makes = [];
    let models = [];
    let years = [];

    // Cache elemek
    const $makeSelect = $('#afs-make');
    const $modelSelect = $('#afs-model');
    const $yearSelect = $('#afs-year');
    const $loading = $('.afs-loading');
    const $error = $('.afs-error');
    const $results = $('.afs-results');

    // Gyártók betöltése
    function loadMakes() {
        $loading.show();
        $error.hide();

        $.ajax({
            url: afsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'afs_get_makes',
                nonce: afsAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    makes = response.data;
                    populateMakeSelect();
                } else {
                    showError('Hiba történt a gyártók betöltése közben.');
                }
            },
            error: function() {
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
            url: afsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'afs_get_models',
                make: make,
                nonce: afsAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    models = response.data;
                    populateModelSelect();
                } else {
                    showError('Hiba történt a modellek betöltése közben.');
                }
            },
            error: function() {
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
            url: afsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'afs_get_years',
                make: make,
                model: model,
                nonce: afsAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    years = response.data;
                    populateYearSelect();
                } else {
                    showError('Hiba történt az évek betöltése közben.');
                }
            },
            error: function() {
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
        makes.forEach(function(make) {
            $makeSelect.append(`<option value="${make.slug}">${make.name}</option>`);
        });
        $makeSelect.prop('disabled', false);
    }

    function populateModelSelect() {
        $modelSelect.empty().append('<option value="">Válassz modellt...</option>');
        models.forEach(function(model) {
            $modelSelect.append(`<option value="${model.slug}">${model.name}</option>`);
        });
        $modelSelect.prop('disabled', false);
    }

    function populateYearSelect() {
        $yearSelect.empty().append('<option value="">Válassz évet...</option>');
        years.forEach(function(year) {
            $yearSelect.append(`<option value="${year}">${year}</option>`);
        });
        $yearSelect.prop('disabled', false);
    }

    // Hibaüzenet megjelenítése
    function showError(message) {
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
    $('#afs-search-form').on('submit', function(e) {
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
            url: afsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'afs_search_wheels',
                make: make,
                model: model,
                year: year,
                nonce: afsAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayResults(response.data);
                } else {
                    showError('Hiba történt a keresés során.');
                }
            },
            error: function() {
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

        wheels.forEach(function(wheel) {
            const wheelCard = `
                <div class="afs-result-card">
                    <h3>${wheel.make} ${wheel.model}</h3>
                    <p>Méret: ${wheel.size}"</p>
                    <p>Szélesség: ${wheel.width}</p>
                    <p>Offset: ${wheel.offset}</p>
                    <p>Csavarok: ${wheel.bolt_pattern}</p>
                </div>
            `;
            $results.append(wheelCard);
        });
    }

    // Inicializálás
    loadMakes();
}); 