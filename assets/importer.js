jQuery(document).ready(function($) {
    var syncButton = $(".romario-sync-erp");
    var buiButton = $(".bulk-upload-images");
    var statusMessage = $(".status-message");
    var AJAX_HANDLER = ajax_handler;
    var totalCountProductsUploaded = 0;

    syncButton.on("click", function() {
        $(".overlay").show();
        startImportingProcess();
    });

    function startImportingProcess() {
        $.ajax({
            url: AJAX_HANDLER.ajax_url,
            method: "GET",
            beforeSend: function() {
                $("#step1").text("Started Downloading Products from ERP").css("color", "blue");
            },
            data: {
                action: 'create_job_for_importing'
            },
            success: (res) => {
                console.log(res);
                console.log("Started Syncing Process");
                // Start checking the sync status
                var checkSyncInterval = setInterval(() => {
                    isSyncCompleted(checkSyncInterval);
                }, 5000); // Check every 5 seconds
            },
            error: (err) => {
                console.warn("Something has gone wrong!");
            }
        });
    }

    function isSyncCompleted(intervalId) {
        $.ajax({
            url: AJAX_HANDLER.ajax_url,
            method: "GET",
            data: {
                action: "is_sync_completed",
            },
            success: function(res) {
                console.log(res);
                $("#progress-count").text(`${res?.data?.total_products_synced} Products Downloaded`);

                if (res.data && res.data.completed) {
                    clearInterval(intervalId);
                    $("#progress-count").text(`All Products downloaded succesfully! Now Inserting the Products into database.`);
                    checkInsertInterval = setInterval(insertProducts, 3000);
                }
            },
            error: function(err) {
                console.error("Something went wrong!");
            }
        });
    }


    function insertProducts()
    {
        $.ajax({
            url: AJAX_HANDLER.ajax_url,
            method: "GET",
            data: {
                action: "insert_products",
            },
            beforeSend:function(){
                console.log("making request again");
            },
            success: function(res) {
                console.log(res);
                if (res.data.insertion_complete) {
                    clearInterval(checkInsertInterval);
                    $("#progress-count").text(`All Products Synced.`);
                    $(".overlay").fadeOut(200);
                } else {
                    $("#progress-count").text(`Inserted ${res.data.products_inserted} products so far...`);
                }
            },
            error: function(err) {
                console.error("Something went wrong!");
            }
        });
    }
});
