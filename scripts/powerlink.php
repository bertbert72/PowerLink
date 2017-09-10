<?php
function postResult($cmd,$ndata) {
    $cookie_name = $GLOBALS['cookie_name'];
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n" .
                         "Accept-language: en-GB,en-US;q=0.8,en;q=0.6\r\n" .
                         "Cookie: {$cookie_name}={$_SESSION[$cookie_name]}",
            'method'  => 'POST',
            'content' => ($ndata != '') ? http_build_query($ndata) : NULL
        )
    );
    $context  = stream_context_create($options);
    return file_get_contents($cmd, false, $context);
}

function getResult($cmd,$ndata) {
    $cookie_name = $GLOBALS['cookie_name'];
    $options = array(
        'http' => array(
            'header'  => "Accept-language: en-GB,en-US;q=0.8,en;q=0.6\r\n" .
                         "Cookie: {$cookie_name}={$_SESSION[$cookie_name]}",
            'method'  => 'GET',
            'content' => ($ndata != '') ? http_build_query($ndata) : NULL
        )
    );
    $context  = stream_context_create($options);
    return file_get_contents($cmd, false, $context);
}

session_start();

$usr = 'Admin';
$pwd = 'Admin123';
$ip = '192.168.0.200';

$cookie_name = 'PowerLink';

if ($_GET) {
    $command = strtolower($_GET['command']);
    $logout = (isset($_GET['logout'])) ? $_GET['logout'] : 'false';
    $debug = (isset($_GET['debug'])) ? $_GET['debug'] : 'false';
    $search_term = (isset($_GET['term'])) ? $_GET['term'] : '';
    $usr = (isset($_GET['user'])) ? $_GET['user'] : $usr;
    $pwd = (isset($_GET['pass'])) ? $_GET['pass'] : $pwd;
    $ip = (isset($_GET['ip'])) ? $_GET['ip'] : $ip;
} else {
    $command = $argv[1];
}
$logout = (strtolower($logout) == 'true');
$debug = (strtolower($debug) == 'true');
$url = 'http://' . $ip;

$cmd_login = $url . '/web/ajax/login.login.ajax.php';
$cmd_logout = $url . '/web/login.php?act=logout';
$cmd_arming = $url . '/web/ajax/security.main.status.ajax.php';
$cmd_status = $url . '/web/ajax/alarm.chkstatus.ajax.php';
$cmd_logs = $url . '/web/ajax/setup.log.ajax.php';
$cmd_autologout = $url . '/web/ajax/system.autologout.ajax.php';
$cmd_search = $url . '/web/ajax/home.search.ajax.php';
$loginpage = $url . '/web/login.php';
$panelpage = $url . '/web/panel.php';
$framepage = $url . '/web/frameSetup_ViewLog.php';

$continue = TRUE;
$login_required = FALSE;

# Determine if new login is required
if (isset($_SESSION[$cookie_name])) {
    $data = array('task' => 'get_auto_logout_params');
    $result = postResult($cmd_autologout,$data);
    if ($result === "" OR strpos($result, '[RELOGIN]') !== false) {
        if ($debug) print ("Info: login required");
        $login_required = TRUE;
        session_unset();
        session_destroy();
        session_start();
    }
} else {
    $login_required = TRUE;
}

if($login_required) {
    $_SESSION[$cookie_name] = md5(uniqid(rand(), true));
    if ($debug) print ("New token: {$_SESSION[$cookie_name]}");
    #$result = getResult($loginpage,'');
    $data = array('user' => $usr, 'pass' => $pwd);
    $result = postResult($cmd_login,$data);
    if ($result === FALSE) {
        print ("Error: login");
        $continue = FALSE;
    } else {
        if ($debug) print ("Login: {$result}");
        $result = getResult($panelpage,'');
        #$result = getResult($framepage,'');
    }
} else {
    if ($debug) print ("Existing token: {$_SESSION[$cookie_name]}");
}

# Status
if ($continue AND ($command == 'status' or $command == 'fullstatus' or $command == 'ministatus')) {
    if (!isset($_SESSION['STATINDEX']) OR $command == 'fullstatus') {
        $_SESSION['STATINDEX'] = 0;
    };
    $data = array('curindex' => $_SESSION['STATINDEX'], 'sesusername' => $usr, 'sesusermanager' => '1');
    $result = postResult($cmd_status,$data);
    if ($result === FALSE OR $result == NULL) {
        print ("Error: status");
    } else {
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadXML($result);
        $xpath = new DOMXPath($doc);
        $query = '/reply/index';
        $entries = $xpath->query($query);
        $indexfound = FALSE;
        foreach ($entries as $entry) {
            $_SESSION['STATINDEX'] = $entry->nodeValue;
            $_SESSION['PREVSTATUS'] = $result;
            $indexfound = TRUE;
        }
        if ($command != 'ministatus' AND !$indexfound AND isset($_SESSION['PREVSTATUS'])) {
            if ($debug) print ($result);
            $result = $_SESSION['PREVSTATUS'];
        }
        print ($result);
    }
}

# Logs
if ($continue AND $command == 'logs') {
    $looplimit = 10;
    $doloop = TRUE;
    while ($doloop AND $looplimit > 0) {
        $result = postResult($cmd_logs,'');
        if ($result === FALSE OR strpos($result, 'Call to undefined function') !== false) {
            if ($debug) print ("Warning: log call failed");
            $looplimit--;
        } else {
            print ($result);
            $doloop = FALSE;
        }
    }
    if ($doloop) {
        print ("Error: logs");
    }
}

# Arm Home
if ($continue AND $command == 'armhome') {
    $data = array('set' => 'ArmHome');
    $result = postResult($cmd_arming,$data);
    if ($result === FALSE) {
        print ("Error: arm home");
    } else {
        print ("ArmHome: {$result}");
    }
}

# Arm Away
if ($continue AND $command == 'armaway') {
    $data = array('set' => 'ArmAway');
    $result = postResult($cmd_arming,$data);
    if ($result === FALSE) {
        print ("Error: arm away");
    } else {
        print ("ArmAway: {$result}");
    }
}

# Disarm
if ($continue AND $command == 'disarm') {
    $data = array('set' => 'Disarm');
    $result = postResult($cmd_arming,$data);
    if ($result === FALSE) {
        print ("Error: disarm");
    } else {
        print ("Disarm: {$result}");
    }
}

# Search TODO
if ($continue AND $command == 'search' AND $search_term != '') {
    $data = array('q' => $search_term);
    $result = postResult($cmd_search,$data);
    if ($result === FALSE) {
        print ("Error: search");
    } else {
        print ("Search: {$result}");
    }
}

# Logout
if ($continue AND ($command == 'logout' OR $logout)) {
    $result = postResult($cmd_logout,'');
    if ($command == 'logout' OR $debug) {
        if ($result === FALSE) {
            print ("Error: logout");
        } else {
            print ("Logout: <OK/>");
        }
    }
}
?>
