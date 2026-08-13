(function($) {
    'use strict';
    
    $(document).ready(function() {
        if (!$('body').hasClass('single-product')) {
            return;
        }
        
        var tabsToCheck = ['description', 'additional_information'];
        var emptyTabs = [];
        
        tabsToCheck.forEach(function(tabId) {
            
            // 1. Trouver le body (contenu réel) de l'onglet
            var $body = $('.product-accordion__item-body.' + tabId + ', .product-accordion__item-body[data-id="' + tabId + '"]').first();
            
            if (!$body.length) {
                return;
            }
            
            // 2. Vérifier si le body est vide (en ignorant le span titre)
            var $clone = $body.clone();
            $clone.find('span').remove();
            
            var textContent = $clone.text().trim();
            var hasImages = $clone.find('img').length > 0;
            var hasTables = $clone.find('table').length > 0;
            var hasLists = $clone.find('ul, ol').length > 0;
            var hasParagraphs = $clone.find('p').filter(function() {
                return $(this).text().trim() !== '';
            }).length > 0;
            var hasDivs = $clone.find('div').filter(function() {
                return $(this).text().trim() !== '';
            }).length > 0;
            
            var isEmpty = (textContent === '' && !hasImages && !hasTables && !hasLists && !hasParagraphs && !hasDivs);
            
            if (isEmpty) {
                emptyTabs.push(tabId);
                
                // Masquer le lien de navigation
                $('.product-tabs__nav-item[data-id="' + tabId + '"]').hide();
                
                // Masquer le titre accordion
                $('.product-accordion__item-title.' + tabId + ', .product-accordion__item-title[data-id="' + tabId + '"]').hide();
                
                // Masquer le body accordion
                $body.hide();
                
                // Masquer le conteneur accordion complet
                $body.closest('.product-accordion__item').hide();
            }
        });
        
        // 3. Si tous les onglets sont vides, masquer le conteneur complet
        if (emptyTabs.length >= tabsToCheck.length) {
            $('.product-tabs__nav, .product-tabs__body, .product-tabs').hide();
        }
        
        // 4. Activer le premier onglet visible si l'actif est masqué
        setTimeout(function() {
            var $activeNav = $('.product-tabs__nav-item--active:visible');
            if ($activeNav.length === 0) {
                var $firstVisible = $('.product-tabs__nav-item:visible').first();
                if ($firstVisible.length) {
                    $firstVisible.trigger('click');
                }
            }
        }, 300);
    });
    
})(jQuery);