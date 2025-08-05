/* Интеграция тарифов с системой Aspro LiteShop */
(function() {
    'use strict';
    
    let initialized = false;
    
    // Ждем загрузки системы Aspro
    function waitForAspro(callback) {
        let attempts = 0;
        const maxAttempts = 100;
        
        function check() {
            attempts++;
            if (typeof BX !== 'undefined' && 
                typeof JItemActionBasket !== 'undefined' &&
                BX.ready) {
                callback();
            } else if (attempts < maxAttempts) {
                setTimeout(check, 100);
            } else {
                console.warn('Aspro system not loaded, trying fallback initialization');
                callback();
            }
        }
        
        check();
    }
    
    // Переопределяем requestUrl для тарифов
    function setupTariffIntegration() {
        if (typeof JItemActionBasket === 'undefined') {
            console.warn('JItemActionBasket not found');
            return false;
        }
        
        // Проверяем, не переопределен ли уже
        if (JItemActionBasket.prototype._tariffUrlOverridden) {
            return true;
        }
        
        // Сохраняем оригинальный requestUrl
        const originalRequestUrl = JItemActionBasket.prototype.requestUrl;
        
        // Переопределяем requestUrl
        Object.defineProperty(JItemActionBasket.prototype, 'requestUrl', {
            get: function() {
                // Проверяем, это тариф?
                const tariffContainer = this.node.closest('.tariff-card, .tariff-detail');
                if (tariffContainer) {
                    return '/local/components/monitoring/tariffs.simple/ajax.php';
                }
                
                // Для всех остальных - стандартный URL
                return (typeof originalRequestUrl === 'string') 
                    ? originalRequestUrl 
                    : (arAsproOptions.SITE_DIR + 'ajax/item.php');
            },
            configurable: true
        });
        
        // Помечаем как переопределенный
        JItemActionBasket.prototype._tariffUrlOverridden = true;

        console.log('Tariff requestUrl override installed');
        return true;
    }
    
    // Инициализация кнопок тарифов
    function initTariffButtons() {
        const tariffButtons = document.querySelectorAll(
            '.tariff-card .js-item-action[data-action="basket"], ' +
            '.tariff-detail .js-item-action[data-action="basket"]'
        );
        
        tariffButtons.forEach(function(button) {
            if (!button.itemAction && typeof JItemAction !== 'undefined') {
                try {
                    JItemAction.factory(button);
                } catch (e) {
                    console.warn('Failed to initialize tariff button:', e);
                }
            }
        });
        
        return tariffButtons.length;
    }
    
    // Обновление состояний элементов
    function updateItemStates() {
        if (typeof JItemAction !== 'undefined') {
            try {
                JItemAction.markItems();
                JItemAction.markBadges();
            } catch (e) {
                console.warn('Failed to update item states:', e);
            }
        }
    }
    
    // Основная инициализация
    function init() {
        if (initialized) {
            console.log('Tariff integration already initialized');
            return;
        }
        
        console.log('Initializing tariff integration...');
        
        // Настраиваем интеграцию
        const integrationSetup = setupTariffIntegration();
        
        // Инициализируем кнопки тарифов
        const buttonCount = initTariffButtons();
        
        // Обновляем состояния
        setTimeout(updateItemStates, 200);
        
        initialized = true;
        
        console.log(`Tariff integration initialized! Integration: ${integrationSetup}, Buttons: ${buttonCount}`);
    }
    
    // Переинициализация (для AJAX обновлений)
    function reinit() {
        initialized = false;
        setTimeout(function() {
            waitForAspro(init);
        }, 100);
    }
    
    // Обработчик изменения количества в детальной странице
    function handleQuantityChange() {
        const quantityFields = document.querySelectorAll('.tariff-detail [data-entity="basket-item-quantity-field"]');
        
        quantityFields.forEach(function(field) {
            if (field._tariffHandlerAttached) return;
            
            field.addEventListener('change', function() {
                const basketBtn = document.querySelector('.tariff-detail [data-action="basket"]');
                if (basketBtn) {
                    basketBtn.setAttribute('data-quantity', this.value);
                }
            });
            
            field._tariffHandlerAttached = true;
        });
    }
    
    // Запуск при загрузке страницы
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            waitForAspro(function() {
                init();
                handleQuantityChange();
            });
        });
    } else {
        waitForAspro(function() {
            init();
            handleQuantityChange();
        });
    }
    
    // Переинициализация при AJAX обновлениях
    if (typeof BX !== 'undefined') {
        BX.ready(function() {
            // Aspro события
            BX.addCustomEvent('onCompleteAction', function(eventdata) {
                if (eventdata && (
                    eventdata.action === 'ajaxContentLoaded' || 
                    eventdata.action === 'jsLoadBlock' ||
                    eventdata.action === 'loadBlockContent'
                )) {
                    setTimeout(reinit, 100);
                }
            });
            
            // События корзины
            BX.addCustomEvent('OnBasketChange', function() {
                setTimeout(updateItemStates, 100);
            });
        });
    }
    
    // Fallback для случаев когда BX не загружен
    document.addEventListener('click', function(e) {
        if (e.target.matches('.js-item-action[data-action="basket"]') && 
            e.target.closest('.tariff-card, .tariff-detail')) {
            
            // Переинициализируем через некоторое время после клика
            setTimeout(function() {
                updateItemStates();
                handleQuantityChange();
            }, 500);
        }
    });
    
    // Экспорт для отладки
    window.TariffIntegration = {
        init: init,
        reinit: reinit,
        isInitialized: function() { return initialized; },
        updateStates: updateItemStates
    };
    
})();