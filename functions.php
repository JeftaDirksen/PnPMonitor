<?php
    function loadDb() {
        global $config;
        $dbFile = $config['DB_FILE'];
        $handle = fopen($dbFile, "c+");
        if(!$handle) die("Unable to open db");
        if(!flock($handle, LOCK_EX)) die("Unable to lock db");
        $contents = "";
        if(filesize($dbFile)) {
            $contents = fread($handle, filesize($dbFile));
            if($contents === false) die("Unable to read db");
        }
        $db = json_decode($contents);
        if(!$db) $db = (object) null;
        return array($db, $handle);
    }

    function saveDb() {
        global $db, $dbhandle;
        if(!ftruncate($dbhandle, 0)) die("Unable to truncate db");
        if(!rewind($dbhandle)) die("Unable to rewind db");
        if(fwrite($dbhandle, json_encode($db)) === false) die("Unable to write db");
    }

    function pageMonitor($name, $url, $text = "") {
        return (object) array(
            "name" => $name,
            "type" => "page",
            "url"  => $url,
            "text" => $text,
        );
    }

    function portMonitor($name, $host, $port) {
        return (object) array(
            "name" => $name,
            "type" => "port",
            "host" => $host,
            "port" => $port,
        );
    }

    function addMonitor($monitor) {
        global $db;
        $db->monitors[] = $monitor;
    }

    function testMonitor($monitor) {
        switch($monitor->type) {
            case "page":
                return testPageLoadTime($monitor);
                break;
            case "port":
                return testPortResponseTime($monitor);
                break;
            default:
                return false;
        }
    }

    function testPortResponseTime($portMonitor) {
        $time1 = microtime(true);
        $connection = @fsockopen(
            $portMonitor->host,
            $portMonitor->port,
            $errno,
            $errstr,
            10
        );
        $time2 = microtime(true);
        if(is_resource($connection)) {
            fclose($connection);
            return (int)round(($time2 - $time1)*1000);
        }
        else return false;
    }

    function testPageLoadTime($pageMonitor) {
        $time1 = microtime(true);
        $page = @file_get_contents($pageMonitor->url);
        $time2 = microtime(true);
        if(
            strlen($page)
            and strlen($pageMonitor->text)
            and stristr($page, $pageMonitor->text)===false
        ) {
            return false;
        }
        elseif($page) return (int)round(($time2 - $time1)*1000);
        else return false;
    }

    function sendMail($subject, $body) {
        global $config;
        require_once 'PHPMailer/src/Exception.php';
        require_once 'PHPMailer/src/PHPMailer.php';
        require_once 'PHPMailer/src/SMTP.php';
        try {
            $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
            $mailer->isSMTP();
            $mailer->SMTPAuth   = true;
            $mailer->Host       = $config['SMTP_HOST'];
            $mailer->Username   = $config['SMTP_USER'];
            $mailer->Password   = $config['SMTP_PASS'];
            $mailer->SMTPSecure = $config['SMTP_SECURE'];
            $mailer->Port       = $config['SMTP_PORT'];
            $mailer->setFrom($config['SMTP_FROM']);
            $mailer->addAddress($config['SMTP_TO']);
            $mailer->Subject = $subject;
            $mailer->Body = $body;
            $mailer->send();
        }
        catch (Exception $e) {
            echo "Message could not be sent. Error: {$mailer->ErrorInfo}";
        }
    }

    function getUser($email) {
        global $db;
        foreach($db->users as $user) {
            if($user->email == $email) return $user;
        }
        return false;
    }

    function getUserFromToken($token) {
        global $db;
        foreach($db->users as $user) {
            if($user->token == $token) return $user;
        }
        return false;
    }

    function newSecret($length = 5) {
        $token = "";
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        for($i = 0; $i < $length; $i++) {
            $rnd = rand(0,strlen($chars)-1);
            $char = $chars{$rnd};
            $token .= $char;
        }
        return $token;
    }

    function confirmLink($secret) {
        $protocol = ($_SERVER['HTTPS']=="on") ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        return $protocol.$host."/?confirm=".$secret;
    }

    function updateUser($user) {
        global $db;
        foreach($db->users as $key => $value) {
            if($value->id == $user->id) {
                $db->users[$key] = $user;
                return;
            }
        }
        $db->users[] = $user;
    }

    function newUserId() {
        global $db;
        $id = 1;
        foreach($db->users as $user) if($user->id >= $id) $id = $user->id+1;
        return $id;
    }

    function verifyLogin($email, $password) {
        global $db;
        foreach($db->users as $user) {
            if($user->email == $email) {
                if(password_verify($password, $user->password)) {
                    return $user;
                }
                else {
                    return false;
                }
            }
        }
        return false;
    }
    
    function confirm($code) {
        global $db;
        foreach($db->users as $user) {
            if($user->confirm == $code) {
                unset($user->confirm);
                updateUser($user);
            }
        }
    }
    
    function thisUrl() {
        $protocol = ($_SERVER['HTTPS']=="on") ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        return $protocol.$host."/";
    }

    function redirect($url = "") {
        if(empty($url)) $url = thisUrl();
        header("Location: $url", true, 303);
        die();
    }
