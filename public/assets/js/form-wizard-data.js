(function ($) {
    'use strict';
    $(document).ready(function () {
        $('#rootwizard').bootstrapWizard({
            tabClass: 'nav nav-pills',
            onTabShow: function(tab, navigation, index) {
                var $total = navigation.find('li').length;
                var $current = index + 1;

                if (index === 0) {
                    $('#rootwizard').find('.nav-pills .previous .nav-link').attr("disabled", true);
                } else {
                    $('#rootwizard').find('.nav-pills .previous .nav-link').removeAttr("disabled");
                }
                if ($current >= $total) {
                    $('#rootwizard').find('.nav-pills .next').hide();
                    $('#rootwizard').find('.nav-pills .finish').show().removeClass('d-none');
                } else {
                    $('#rootwizard').find('.nav-pills .next').show();
                    $('#rootwizard').find('.nav-pills .finish').hide();
                }
            },
            onNext: function(tab, navigation, index) {
            },
            onPrevious: function(tab, navigation, index) {
            },
            onInit: function() {
            }

        });
    });
})(window.jQuery);