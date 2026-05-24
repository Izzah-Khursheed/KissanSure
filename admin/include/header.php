<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KissanSure Admin</title>

    <!-- bootstrap css -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <!-- bootstrap icons -->
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
     <!-- font awesome cdn -->
      <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet">
    <!-- css styling link -->
    <link rel="stylesheet" href="./css/style1.css">

    <style>
        /* Form labels and filled input text: black. Only placeholders stay grey. */
        .form-label,
        .form-check-label {
            color: #000 !important;
        }
        .form-control,
        .form-select {
            color: #000 !important;
        }
        .form-control::placeholder {
            color: #6c757d !important;
            opacity: 1;
        }
    </style>

    <script>
    function showFlash(msg, type) {
        var icons = {success:'bi-check-circle-fill',danger:'bi-x-circle-fill',warning:'bi-exclamation-triangle-fill',info:'bi-info-circle-fill'};
        var c = document.getElementById('_fc');
        if (!c) {
            c = document.createElement('div');
            c.id = '_fc';
            c.style.cssText = 'position:fixed;top:80px;right:20px;z-index:99999;width:340px;';
            document.body.appendChild(c);
        }
        var d = document.createElement('div');
        d.style.marginBottom = '8px';
        d.className = 'alert alert-'+(type||'info')+' shadow d-flex align-items-start gap-2 p-3';
        d.style.borderRadius = '10px';
        d.innerHTML = '<i class="bi '+(icons[type]||'bi-info-circle-fill')+' flex-shrink-0 mt-1"></i>'
                    + '<div class="flex-grow-1 small">'+msg+'</div>'
                    + '<button type="button" onclick="this.closest(\'.alert\').remove()" style="background:none;border:none;padding:2px 6px;opacity:.6;font-size:1.2rem;line-height:1;cursor:pointer;">&times;</button>';
        c.appendChild(d);
        setTimeout(function(){
            d.style.cssText += 'transition:opacity .4s;opacity:0;';
            setTimeout(function(){ if(d.parentNode) d.parentNode.removeChild(d); }, 420);
        }, 5000);
    }
    </script>
</head>
<body>
<?php
if (!empty($_SESSION['flash'])) {
    $fm = htmlspecialchars($_SESSION['flash']['msg'], ENT_QUOTES);
    $ft = htmlspecialchars($_SESSION['flash']['type']);
    echo "<script>document.addEventListener('DOMContentLoaded',function(){ showFlash('$fm','$ft'); });</script>";
    unset($_SESSION['flash']);
}
?>