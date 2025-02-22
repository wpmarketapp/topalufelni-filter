jQuery(document).ready(function($) {
    let makes = [];
    let models = [];
    let years = [];

    // Cache elemek
    const $makeSelect = $('#taf-make');
    const $modelSelect = $('#taf-model');
    const $yearSelect = $('#taf-year');
    const $error = $('.taf-error');
    const $results = $('.taf-results');
    const $searchButton = $('#taf-search-form button[type="submit"]');

    // Betöltési állapot kezelése
    function setLoading(isLoading) {
        if (isLoading) {
            $searchButton.prop('disabled', true).text('Betöltés...');
        } else {
            $searchButton.prop('disabled', false).text('Keresés');
        }
    }

    // Session adatok visszatöltése
    function restoreSessionData() {
        const sessionData = sessionStorage.getItem('taf_filter_data');
        if (sessionData) {
            const data = JSON.parse(sessionData);
            
            // Csak akkor állítsuk vissza a találatokat, ha van kiválasztott gyártó
            if (data.make) {
                // Gyártók betöltése után állítjuk be a kiválasztott értéket
                loadMakes().then(() => {
                    $makeSelect.val(data.make);
                    // Modellek betöltése után állítjuk be a kiválasztott értéket
                    loadModels(data.make).then(() => {
                        if (data.model) {
                            $modelSelect.val(data.model);
                            // Évek betöltése után állítjuk be a kiválasztott értéket
                            loadYears(data.make, data.model).then(() => {
                                if (data.year) {
                                    $yearSelect.val(data.year);
                                }
                                // Csak akkor jelenítjük meg a találatokat, ha minden érték ki van választva
                                if (data.results && data.make && data.model && data.year) {
                                    displayResults(data.results);
                                }
                            });
                        }
                    });
                });
            } else {
                loadMakes();
                $results.empty(); // Ha nincs kiválasztott gyártó, töröljük a találatokat
            }
        } else {
            loadMakes();
            $results.empty(); // Ha nincs mentett adat, töröljük a találatokat
        }
    }

    // Session adatok mentése
    function saveSessionData() {
        const data = {
            make: $makeSelect.val(),
            model: $modelSelect.val(),
            year: $yearSelect.val(),
            results: window.lastResponse && window.lastResponse.success ? window.lastResponse.data : null
        };
        sessionStorage.setItem('taf_filter_data', JSON.stringify(data));
    }

    // Gyártók betöltése
    function loadMakes() {
        setLoading(true);
        $error.hide();
        $makeSelect.prop('disabled', true);

        return new Promise((resolve, reject) => {
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
                        resolve();
                    } else {
                        const message = response.data && response.data.message 
                            ? response.data.message 
                            : 'Hiba történt a gyártók betöltése közben.';
                        showError(message);
                        reject(message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Makes error:', textStatus, errorThrown);
                    showError('Hiba történt a szerverrel való kommunikáció során.');
                    reject(errorThrown);
                },
                complete: function() {
                    setLoading(false);
                }
            });
        });
    }

    // Modellek betöltése
    function loadModels(make) {
        setLoading(true);
        $error.hide();
        $modelSelect.empty().append('<option value="">Válassz modellt...</option>').prop('disabled', true);
        $yearSelect.empty().append('<option value="">Válassz évet...</option>').prop('disabled', true);

        return new Promise((resolve, reject) => {
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
                        resolve();
                    } else {
                        const message = response.data && response.data.message 
                            ? response.data.message 
                            : 'Hiba történt a modellek betöltése közben.';
                        showError(message);
                        reject(message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Models error:', textStatus, errorThrown);
                    showError('Hiba történt a szerverrel való kommunikáció során.');
                    reject(errorThrown);
                },
                complete: function() {
                    setLoading(false);
                }
            });
        });
    }

    // Évek betöltése
    function loadYears(make, model) {
        setLoading(true);
        $error.hide();
        $yearSelect.empty().append('<option value="">Válassz évet...</option>').prop('disabled', true);

        return new Promise((resolve, reject) => {
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
                        resolve();
                    } else {
                        const message = response.data && response.data.message 
                            ? response.data.message 
                            : 'Hiba történt az évek betöltése közben.';
                        showError(message);
                        reject(message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Years error:', textStatus, errorThrown);
                    showError('Hiba történt a szerverrel való kommunikáció során.');
                    reject(errorThrown);
                },
                complete: function() {
                    setLoading(false);
                }
            });
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

    // Keresés indítása
    function initSearch() {
        $('#taf-search-form').off('submit').on('submit', function(e) {
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
    }

    function searchWheels(make, model, year) {
        setLoading(true);
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
                window.lastResponse = response;

                if (response.success && Array.isArray(response.data)) {
                    displayResults(response.data);
                    saveSessionData();
                } else {
                    let message = response.data && response.data.message 
                        ? response.data.message 
                        : 'Hiba történt a keresés során.';

                    if (response.data && response.data.debug) {
                        const debug = response.data.debug;
                        message += '<div class="taf-debug-info" style="background: #fff; padding: 20px; margin-top: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
                        message += '<h4 style="color: #000; margin-bottom: 15px;">Debug Információk:</h4>';
                        
                        if (debug.api_sizes) {
                            message += '<div style="margin-bottom: 10px;">';
                            message += '<p style="color: #000; margin: 5px 0;"><strong>API által kapott felni méret:</strong></p>';
                            message += '<p style="color: #000; background: #f8f9fa; padding: 10px; border-radius: 4px;">' + debug.api_sizes + '</p>';
                            message += '</div>';
                        }
                        
                        if (debug.available_sizes) {
                            message += '<div style="margin-bottom: 10px;">';
                            message += '<p style="color: #000; margin: 5px 0;"><strong>Elérhető termékek méretei:</strong></p>';
                            message += '<p style="color: #000; background: #f8f9fa; padding: 10px; border-radius: 4px;">' + debug.available_sizes + '</p>';
                            message += '</div>';
                        }
                        
                        message += '</div>';
                    }
                    
                    $results.html(message);
                }
                
                initSearch();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Wheels error:', textStatus, errorThrown);
                showError('Hiba történt a szerverrel való kommunikáció során.');
                initSearch();
            },
            complete: function() {
                setLoading(false);
            }
        });
    }

    function displayResults(wheels) {
        if (!wheels.length) {
            let errorMessage = 'Nem található elérhető felni a megadott paraméterekkel.';
            
            // Debug információk megjelenítése
            if (window.lastResponse && window.lastResponse.data && window.lastResponse.data.debug) {
                const debug = window.lastResponse.data.debug;
                errorMessage += '<div class="taf-debug-info" style="background: #fff; padding: 20px; margin-top: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
                errorMessage += '<h4 style="color: #000; margin-bottom: 15px;">Debug Információk:</h4>';
                
                if (debug.api_sizes) {
                    errorMessage += '<div style="margin-bottom: 10px;">';
                    errorMessage += '<p style="color: #000; margin: 5px 0;"><strong>API által kapott felni méret:</strong></p>';
                    errorMessage += '<p style="color: #000; background: #f8f9fa; padding: 10px; border-radius: 4px;">' + debug.api_sizes + '</p>';
                    errorMessage += '</div>';
                }
                
                if (debug.available_sizes) {
                    errorMessage += '<div style="margin-bottom: 10px;">';
                    errorMessage += '<p style="color: #000; margin: 5px 0;"><strong>Elérhető termékek méretei:</strong></p>';
                    errorMessage += '<p style="color: #000; background: #f8f9fa; padding: 10px; border-radius: 4px;">' + debug.available_sizes + '</p>';
                    errorMessage += '</div>';
                }
                
                errorMessage += '</div>';
            }
            
            $results.html(errorMessage);
            return;
        }

        let resultsHtml = '';
        wheels.forEach(function(wheel) {
            resultsHtml += `
                <div class="taf-result-card">
                    <a href="${wheel.permalink}" class="taf-product-link">
                        ${wheel.image_url ? 
                            `<div class="taf-product-image">
                                <img src="${wheel.image_url}" alt="${wheel.name}">
                            </div>` : 
                            ''
                        }
                        <h3>${wheel.name}</h3>
                        <div class="taf-product-price">
                            ${wheel.sale_price ? 
                                `<span class="taf-sale-price">${wheel.sale_price} Ft</span>
                                 <span class="taf-regular-price">${wheel.regular_price} Ft</span>` : 
                                `<span class="taf-price">${wheel.price} Ft</span>`
                            }
                        </div>
                        <div class="taf-specs">
                            <span>Méret: ${wheel.size}"</span>
                            ${wheel.bolt_pattern ? `<span>Osztókör: ${wheel.bolt_pattern}</span>` : ''}
                        </div>
                    </a>
                </div>
            `;
        });

        // Debug információk hozzáadása az eredményekhez
        if (window.lastResponse && window.lastResponse.data && window.lastResponse.data.debug) {
            const debug = window.lastResponse.data.debug;
            resultsHtml += '<div class="taf-debug-info" style="background: #fff; padding: 20px; margin-top: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
            resultsHtml += '<h4 style="color: #000; margin-bottom: 15px;">Debug Információk:</h4>';
            
            if (debug.api_sizes) {
                resultsHtml += '<div style="margin-bottom: 10px;">';
                resultsHtml += '<p style="color: #000; margin: 5px 0;"><strong>API által kapott felni méret:</strong></p>';
                resultsHtml += '<p style="color: #000; background: #f8f9fa; padding: 10px; border-radius: 4px;">' + debug.api_sizes + '</p>';
                resultsHtml += '</div>';
            }
            
            if (debug.available_sizes) {
                resultsHtml += '<div style="margin-bottom: 10px;">';
                resultsHtml += '<p style="color: #000; margin: 5px 0;"><strong>Elérhető termékek méretei:</strong></p>';
                resultsHtml += '<p style="color: #000; background: #f8f9fa; padding: 10px; border-radius: 4px;">' + debug.available_sizes + '</p>';
                resultsHtml += '</div>';
            }
            
            resultsHtml += '</div>';
        }

        console.log('Megjelenítendő HTML:', resultsHtml); // Debug log
        $results.html(resultsHtml);
    }

    // Minden felni gomb kezelése
    $('#taf-all-wheels').on('click', function(e) {
        e.preventDefault();
        getAllWheels();
    });

    function getAllWheels() {
        setLoading(true);
        $error.hide();
        $results.empty();

        $.ajax({
            url: tafAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'taf_get_all_wheels',
                nonce: tafAjax.nonce
            },
            success: function(response) {
                console.log('All wheels response:', response);
                if (response.success && Array.isArray(response.data)) {
                    displayAllWheels(response.data);
                } else {
                    const message = response.data && response.data.message 
                        ? response.data.message 
                        : 'Hiba történt a felnik betöltése közben.';
                    showError(message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('All wheels error:', textStatus, errorThrown);
                showError('Hiba történt a szerverrel való kommunikáció során.');
            },
            complete: function() {
                setLoading(false);
            }
        });
    }

    function displayAllWheels(wheels) {
        if (!wheels.length) {
            $results.html('<p>Nem található elérhető felni.</p>');
            return;
        }

        let resultsHtml = '';
        wheels.forEach(function(wheel) {
            resultsHtml += `
                <div class="taf-result-card">
                    <a href="${wheel.permalink}" class="taf-product-link">
                        ${wheel.image_url ? 
                            `<div class="taf-product-image">
                                <img src="${wheel.image_url}" alt="${wheel.name}">
                            </div>` : 
                            ''
                        }
                        <h3>${wheel.name}</h3>
                        <div class="taf-product-price">
                            ${wheel.sale_price ? 
                                `<span class="taf-sale-price">${wheel.sale_price} Ft</span>
                                 <span class="taf-regular-price">${wheel.regular_price} Ft</span>` : 
                                `<span class="taf-price">${wheel.price} Ft</span>`
                            }
                        </div>
                        <div class="taf-specs">
                            ${wheel.size ? `<span>Méret: ${wheel.size}"</span>` : ''}
                            ${wheel.bolt_pattern ? `<span>Osztókör: ${wheel.bolt_pattern}</span>` : ''}
                        </div>
                    </a>
                </div>
            `;
        });

        $results.html(resultsHtml);
    }

    // Event listeners
    $makeSelect.on('change', function() {
        const selectedMake = $(this).val();
        $results.empty(); // Találatok törlése minden változtatásnál
        
        if (selectedMake) {
            loadModels(selectedMake).then(() => {
                saveSessionData();
                initSearch();
            });
        } else {
            $modelSelect.empty().append('<option value="">Válassz modellt...</option>').prop('disabled', true);
            $yearSelect.empty().append('<option value="">Válassz évet...</option>').prop('disabled', true);
            saveSessionData();
            initSearch();
        }
    });

    $modelSelect.on('change', function() {
        const selectedMake = $makeSelect.val();
        const selectedModel = $(this).val();
        $results.empty(); // Találatok törlése modell változtatásnál is
        
        if (selectedMake && selectedModel) {
            loadYears(selectedMake, selectedModel).then(() => {
                saveSessionData();
                initSearch();
            });
        } else {
            $yearSelect.empty().append('<option value="">Válassz évet...</option>').prop('disabled', true);
            saveSessionData();
            initSearch();
        }
    });

    $yearSelect.on('change', function() {
        $results.empty(); // Találatok törlése év változtatásnál is
        saveSessionData();
        initSearch();
    });

    // Inicializálás - most a sessionStorage-ből töltjük vissza az adatokat
    restoreSessionData();
    initSearch();
}); 