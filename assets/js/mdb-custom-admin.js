(function ($) {

    /*
     * Responsible for additional js functions
     */

    jQuery(document).ready(function () {



        jQuery(document).on('click', '#mdb-load-products', function (e) {
            e.preventDefault();

            var $databaseCus = jQuery('#customer-database').val();
            var $emailAddressCus = jQuery('#customer-email-address').val();
            var $product_import_limit = parseInt(jQuery('#product-import-limit').val(), 10);

            var $ajaxUrl = mdb_admin_global.ajaxurl;
            var $currentObj = jQuery(this);
            var $progressBar = $('#progress-bar');
            var $progressContainer = $('#progress-container');
            var $btnWrap = jQuery('.mdb-btn-wrap');

            $currentObj.html('Import Products <img src="' + mdb_admin_global.loading_img + '" alt="loading" style="height:15px;" />');
            $progressContainer.show();

            // Calculate interval and increment based on product import limit
            var maxDuration = 300000; // 5 minutes in milliseconds
            var baseInterval = 5000; // Base interval for updates in milliseconds

            var totalUpdates = Math.min(100, $product_import_limit); // Limit total updates to 100 or the number of products
            var intervalDuration = Math.floor(maxDuration / totalUpdates); // Adjust interval based on the number of products
            var incrementAmount = 90 / totalUpdates; // Distribute 90% progress over the number of updates

            // Ensure the interval duration is reasonable (minimum 100ms)
            intervalDuration = Math.max(100, intervalDuration);

            var currentWidth = 0;

            // Simulate progress
            var progressInterval = setInterval(function () {
                if (currentWidth < 90) {
                    currentWidth += incrementAmount;
                    $progressBar.css('width', currentWidth + '%').html(Math.round(currentWidth) + '%');
                }
            }, intervalDuration);

            var request = $.ajax({
                url: $ajaxUrl,
                method: "POST",
                data: {
                    action: 'mdb_load_customer_products',
                    database: $databaseCus,
                    email: $emailAddressCus,
                    limit:$product_import_limit
                },
                dataType: "json"
            });

            request.done(function (response) {
                clearInterval(progressInterval);
                $currentObj.html('Import Products');
                $btnWrap.html(response.message);
                $progressBar.css('width', '100%').html('100%');
                setTimeout(function () {
                    $progressContainer.fadeOut();
                }, 2000);
            });

            request.fail(function (jqXHR, textStatus, errorThrown) {
                clearInterval(progressInterval);
                $btnWrap.html('Error: ' + textStatus);
                $progressBar.css('width', '100%').html('Error');
                setTimeout(function () {
                    $progressContainer.fadeOut();
                }, 2000);
            });

            return false;
        });




    });


})(jQuery);