<?php
require_once('init.php');
list($db, $dbhandle) = loadDb();

// Register
if(isset($_POST['registerForm'])) {
    if(!$db->settings->allowRegister) message("Registration not allowed", true, "?p=login");
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];

    if(!$email) message("Invalid email", true, "?p=register");

    if(strlen($password) < 5)
        message("Password must be at least 5 characters long", true, "?p=register");

    if($password <> $password2)
        message("Passwords do not match", true, "?p=register");

    $user = getUser($email);
    if($user) message("A user with this email already exists", true, "?p=register");

    $newsecret = newSecret();
    sendMail($email, "PnPMonitor email confirmation", confirmLink($newsecret));
    $user = (object) null;
    $user->id = newUserId();
    $user->email = $email;
    $user->password = password_hash($password, PASSWORD_DEFAULT);
    $user->confirm = $newsecret;
    $db->users[] = $user;
    saveDb();
    message("An email has been sent for confirmation");
}

// Resend confirmation code
if(isset($_GET['resend'])) {
    $user = getUser($_GET['email']);
    sendMail($user->email, "PnPMonitor email confirmation", confirmLink($user->confirm));
    message("An email has been sent for confirmation");
}

// Confirm
if(isset($_GET['confirm'])) {
    confirm($_GET['confirm']);
    saveDb();
    message("Email has been confirmed");
}

// Login
if(isset($_POST['loginForm'])) {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $user = verifyLogin($email, $password);
    if(!$user) message("Incorrect username or password", true, "?p=login");
    if(isset($user->confirm) && !isset($user->newemail)) message("Email has to be confirmed first, ".
        "find the confirmation link in your mailbox ".
        "(<a href=\"action.php?resend&email=$email\">resend</a>)", false, "?p=login");
    $_SESSION['id'] = $user->id;
    redirect();
}

// Login check
loginRequired();
$userid = $_SESSION['id'];
// --- Must be logged in for below actions ---

// Save monitor
if(isset($_POST['saveMonitor'])) {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    if(!$name) message("Invalid name", true, "?p=edit&id=".$_POST['id']);
    if($_POST['type'] == "page") {
        $url = filter_var($_POST['field1'], FILTER_VALIDATE_URL);
        $text = filter_var($_POST['field2'], FILTER_SANITIZE_STRING);
        if(!$url) message("Invalid url", true, "?p=edit&id=".$_POST['id']);
        $monitor = pageMonitor($userid, $name, $url, $text);
    }
    elseif($_POST['type'] == "port") {
        $host = filter_var($_POST['field1'], FILTER_VALIDATE_DOMAIN,
                           FILTER_FLAG_HOSTNAME);
        $port = filter_var($_POST['field2'], FILTER_VALIDATE_INT);
        if(!$host) message("Invalid host", true, "?p=edit&id=".$_POST['id']);
        if(!$port) message("Invalid port", true, "?p=edit&id=".$_POST['id']);
        $monitor = portMonitor($userid, $name, $host, $port);
    }
    if($_POST['id'] == "new") addMonitor($monitor);
    else {
        $monitor->id = (int)$_POST['id'];
        editMonitor($monitor);
    }
    saveDb();
    redirect("?p=monitor&id=".$_POST['id']);
}

// Save settings
if(isset($_POST['settingsForm'])) {
    $db->settings->allowRegister = isset($_POST['allowRegister']) ? true : false;
    $db->settings->smtpHost = $_POST['smtpHost'];
    $db->settings->smtpSecure = $_POST['smtpSecure'];
    $db->settings->smtpPort = $_POST['smtpPort'];
    $db->settings->smtpUser = $_POST['smtpUser'];
    $db->settings->smtpPass = $_POST['smtpPass'];
    $db->settings->smtpFrom = $_POST['smtpFrom'];
    saveDb();
    message("Changes saved", false, "?p=settings");
}

// Delete monitor
if(isset($_GET['delete'])) {
    $monitor = getMonitor($_GET['delete']);
    if($monitor->user <> $userid) message("Not your monitor", true);
    if($monitor->type == "page") {
        $_SESSION['pagename'] = $monitor->name;
        $_SESSION['url'] = $monitor->url;
        $_SESSION['text'] = $monitor->text;
    }
    else {
        $_SESSION['portname'] = $monitor->name;
        $_SESSION['host'] = $monitor->host;
        $_SESSION['port'] = $monitor->port;
    }
    deleteMonitor($monitor);
    saveDb();
    redirect("?p=monitors");
}

// Logout
if(isset($_GET['logout'])) {
    session_unset();
    message("Logged out", false, "?p=login");
}

// Account
if(isset($_POST['accountForm'])) {
    $user = getUser();
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $currentpassword = $_POST['currentpassword'];
    $newpassword = $_POST['newpassword'];
    $newpassword2 = $_POST['newpassword2'];

    if(!verifyLogin($user->email, $currentpassword)) message("Incorrect password", true, "?p=account");
    if(!$email) message("Invalid email", true, "?p=account");

    // Password change
    if(!empty($newpassword)) {
        if(strlen($newpassword) < 5)
            message("Password must be at least 5 characters long", true, "?p=account");

        if($newpassword <> $newpassword2)
            message("Passwords do not match", true, "?p=account");

        $user->password = password_hash($newpassword, PASSWORD_DEFAULT);
        updateUser($user);
        saveDb();
    }

    // Email change
    if($email <> $user->email) {
        $test = getUser($email);
        if($test) message("A user with this email already exists", true, "?p=account");
        $newsecret = newSecret();
        sendMail($email, "PnPMonitor email confirmation", confirmLink($newsecret));
        $user->newemail = $email;
        $user->confirm = $newsecret;
        updateUser($user);
        saveDb();
        message("An email has been sent for confirmation");
    }

    message("Changes saved", false, "?p=account");
}
