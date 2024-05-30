jQuery(document).ready(function($){

    var syncButton = $(".romario-sync-erp");
    var buiButton = $(".bulk-upload-images");
    var statusMessage = $(".status-message");
    var AJAX_HANDLER = ajax_handler;

    $(syncButton).on("click", function(){
        $(syncButton).text('Syncing Products... May take a few minutes')
        $.ajax({
            url: AJAX_HANDLER.ajax_url,
            method:"POST",
            data:{
                action:'import_products_from_erp',
            },
            success:function(response){
                $(syncButton).text('Sync Products Now')
                console.log(response)
            },
            error:function(error){
                console.log(error)
            }
        })
    })


    $('.bulk-upload-images').click(function (e) {
        e.preventDefault();
        var custom_uploader = wp.media({
            title: 'Bulk Image Uploader ',
            button: {
                text: 'Select'
            },
            multiple: true
        });

        custom_uploader.on('select', function () {
            $(buiButton).text("Uploading Images");
            var attachments = custom_uploader.state().get('selection').toJSON();
            $.ajax({
                url: AJAX_HANDLER.ajax_url,
                method:"POST",
                data:{
                    action:'upload_bulk_images',
                    imageNames:attachments
                },
                success:function(response){
                    $(buiButton).text('Upload Images')
                    console.log(response)
                },
                error:function(error){
                    console.log(error)
                }
            })
        });

        custom_uploader.open();
    });


})