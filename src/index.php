<?php
    require_once('init.php');
    $title = "Page and Port Monitor";
    $page = 'menu.php';
    $p = $_GET['p'] ?? null;
    switch($p) {
        case 'login':
            $title .= " - Login";
            $page = 'login.php';
        break;
        case 'register':
            $title .= " - Register";
            $page = 'register.php';
        break;
        case 'monitors':
            $title .= " - Monitors";
            $page = 'monitors.php';
        break;
        case 'monitor':
            $title .= " - Monitor";
            $page = 'monitor.php';
        break;
        case 'edit':
            $title .= " - Monitor";
            $page = 'monitor-edit.php';
        break;
        case 'settings':
            $title .= " - Settings";
            $page = 'settings.php';
        break;
        case 'account':
            $title .= " - My account";
            $page = 'account.php';
        break;
        case 'graph':
            $title .= " - Graph";
            $page = 'graph.php';
            $monitorid = (int)$_GET['id'];
            $large = true;
        break;
    }
?>
<html>
<head>
<title><?php echo $title; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
<link rel="manifest" href="manifest.json">
</head>
<body>
<div id="header"><?php echo $title; ?></div>
<?php @include($page); ?>
</body>
</html>
