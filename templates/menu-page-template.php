<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
  </head>
  <body>
    <style>
        .notice{
            display: none!important;
        }
        .wrapper {
            height: 100vh;
        }

        button {
            background: #2142e5;
            color: #fff;
            border-radius: 5px;
            padding: 10px 30px;
            border: none;
        }
        
    
    </style>
    <div class="wrapper d-flex align-items-center justify-content-center">
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6 mx-auto text-center">
                <img class="mb-3" src="<?php echo ROMARIO_PUBLIC_ASSETS . '/logo-original.png' ?>" alt="logo-original">
                <h4>Sync Romario ERP with WooCommerce</h4>
                <p><strong>Last Synced On: </strong> <?php echo get_option('romario_last_synced', true); ?></p>
                <button class="romario-sync-erp">Sync Products Now</button>
                <p class="status-message"></p>

                <br>
                <br>
                <hr>
                <h4>Bulk Upload Images</h4>
                <button class="bulk-upload-images">Upload Images</button>
                <p class="status-message"></p>
            </div>
        </div>
    </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
  </body>
</html>