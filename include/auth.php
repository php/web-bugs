<?php
/*
   +----------------------------------------------------------------------+
   | PEAR Web site version 1.0                                            |
   +----------------------------------------------------------------------+
   | Copyright (c) 2001-2005 The PHP Group                                |
   +----------------------------------------------------------------------+
   | This source file is subject to version 2.02 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available at through the world-wide-web at                           |
   | http://www.php.net/license/2_02.txt.                                 |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Authors:                                                             |
   +----------------------------------------------------------------------+
   $Id$
*/

include_once 'pear-database-user.php';

function auth_reject($realm = null, $message = null)
{
    global $format;

    if ($realm === null) {
        $realm = PEAR_AUTH_REALM;
    }
    if ($message === null) {
        $message = "Please enter your username and password:";
    }

    response_header('Login');
    if ($format == 'xmlrpc') {
        Header("HTTP/1.0 401 Unauthorized");
        Header("WWW-authenticate: basic realm=\"$realm\"");
        report_error($message);
    } elseif ($format == 'html') {
        $GLOBALS['ONLOAD'] = "document.login.PEAR_USER.focus();";
        if ($message) {
            report_error($message);
        }

        if (DEVBOX == false) {
            $action = "https://" . $_SERVER['SERVER_NAME'] . "/login.php";
        } else {
            $action = "/login.php";
        }
        print "<script type=\"text/javascript\" src=\"/javascript/md5.js\"></script>\n";
        print "<script type=\"text/javascript\">\n";
        print "function doMD5(frm) {\n";
        print "    frm.PEAR_PW.value = hex_md5(frm.PEAR_PW.value);\n";
        print "    frm.isMD5.value = 1;\n";
        print "}\n";
        print "</script>\n";
        print "<form onsubmit=\"javascript:doMD5(document.forms['login'])\" name=\"login\" action=\"" . $action . "\" method=\"post\">\n";
        print "<input type=\"hidden\" name=\"isMD5\" value=\"0\" />\n";
        print '<table class="form-holder" cellspacing="1">' . "\n";
        print " <tr>\n";
        print '  <th class="form-label_left">';
        print 'Use<span class="accesskey">r</span>name or email address:</th>' . "\n";
        print '  <td class="form-input">';
        print '<input size="20" name="PEAR_USER" accesskey="r" /></td>' . "\n";
        print " </tr>\n";
        print " <tr>\n";
        print '  <th class="form-label_left">Password:</th>' . "\n";
        print '  <td class="form-input">';
        print '<input size="20" name="PEAR_PW" type="password" /></td>' . "\n";
        print " </tr>\n";
        print " <tr>\n";
        print '  <th class="form-label_left">&nbsp;</th>' . "\n";
        print '  <td class="form-input" style="white-space: nowrap">';
        print '<input type="checkbox" name="PEAR_PERSIST" value="on" id="pear_persist_chckbx" /> ';
        print '<label for="pear_persist_chckbx">Remember username and password.</label></td>' . "\n";
        print " </tr>\n";
        print " <tr>\n";
        print '  <th class="form-label_left">&nbsp;</td>' . "\n";
        print '  <td class="form-input"><input type="submit" value="Log in!" /></td>' . "\n";
        print " </tr>\n";
        print "</table>\n";
        print '<input type="hidden" name="PEAR_OLDURL" value="';
        if (isset($_GET['redirect']) && is_string($_GET['redirect']) &&
              !strpos($_GET['redirect'], '://')) {
            print htmlspecialchars(urldecode($_GET['redirect']));
        } elseif (isset($_POST['PEAR_OLDURL']) && is_string($_POST['PEAR_OLDURL']) &&
              !strpos($_POST['PEAR_OLDURL'], '://')) {
            print htmlspecialchars($_POST['PEAR_OLDURL']);
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            print htmlspecialchars($_SERVER['REQUEST_URI']);
        } else {
            print 'login.php';
        }
        print "\" />\n";
        print "</form>\n";
        print "<hr />\n";
        print "<p><strong>Note:</strong> If you just want to browse the website, ";
        print "you will not need to log in. For all tasks that require ";
        print "authentication, you will be redirected to this form ";
        print "automatically. You can sign up for an account ";
        print "<a href=\"/account-request.php\">over here</a>.</p>";
        print "<p>If you forgot your password, instructions for resetting ";
        print "it can be found on a <a href=\"https://" . PEAR_CHANNELNAME .
              "/about/forgot-password.php\">";
        print "dedicated page</a>.</p>";

    }
    response_footer();
    exit;
}

function auth_verify($user, $passwd)
{
    global $dbh, $auth_user;

    if (empty($auth_user)) {
        $data = user::info($user, null, true, false);
        $auth_user = new PEAR_Auth();
        $auth_user->data($data);
    }
    $error = '';
    $ok = false;
    switch (strlen(@$auth_user->password)) {
        // handle old-style DES-encrypted passwords
        case 13: {
            $seed = substr($auth_user->password, 0, 2);
            $crypted = crypt($passwd, $seed);
            if ($crypted == @$auth_user->password) {
                $ok = true;
            } else {
                $error = "pear-auth: user `$user': invalid password (des)";
            }
            break;
        }
        // handle new-style MD5-encrypted passwords
        case 32: {
            // Check if the passwd is already md5()ed
            if (preg_match('/^[a-z0-9]{32}\z/', $passwd)) {
                $crypted = $passwd;
            } else {
                $crypted = md5($passwd);
            }

            if ($crypted == @$auth_user->password) {
                $ok = true;
            } else {
                $error = "pear-auth: user `$user': invalid password (md5)";
            }
            break;
        }
    }
    if (empty($auth_user->registered)) {
        if ($user) {
            $error = "pear-auth: user `$user' not registered";
        }
        $ok = false;
    }
    if ($ok) {
        $auth_user->_readonly = true;
        return auth_check("pear.user");
    }
    if ($error) {
        error_log("$error\n", 3, PEAR_TMPDIR . DIRECTORY_SEPARATOR . 'pear-errors.log');
    }
    $auth_user = null;
    return false;
}

function auth_check($atom)
{
    global $dbh;
    static $karma;

    require_once "Damblan/Karma.php";

    global $auth_user;

    if (!isset($auth_user)) {
        return false;
    }
    // Check for backwards compatibility
    if (is_bool($atom)) {
        $atom = $atom == true ? 'pear.admin' : 'pear.dev';
    }

    if (!isset($karma)) {
        $karma = new Damblan_Karma($dbh);
    }
    return $karma->has($auth_user->handle, $atom);
}

function auth_require($admin = false)
{
    global $auth_user;

    $res = true;

    $user = @$_COOKIE['PEAR_USER'];
    $passwd = @$_COOKIE['PEAR_PW'];
    if (!auth_verify($user, $passwd)) {
        auth_reject(); // exits
    }

    $num = func_num_args();
    for ($i = 0; $i < $num; $i++) {
        $arg = func_get_arg($i);
        $res = auth_check($arg);

        if ($res == true) {
            return true;
        }
    }

    if ($res == false) {
        response_header("Insufficient Privileges");
        report_error("Insufficient Privileges");
        response_footer();
        exit;
    }

    return true;
}

/**
 * Perform logout for the current user
 */
function auth_logout($self)
{
    if (isset($_COOKIE['PEAR_USER'])) {
        setcookie('PEAR_USER', '', 0, '/');
        unset($_COOKIE['PEAR_USER']);
    }
    if (isset($_COOKIE['PEAR_PW'])) {
        setcookie('PEAR_PW', '', 0, '/');
        unset($_COOKIE['PEAR_PW']);
    }

    if ($_SERVER['QUERY_STRING'] == 'logout=1') {
        localRedirect($self);
    } else {
        localRedirect("{$self}?" . preg_replace('/logout=1/', '', $_SERVER['QUERY_STRING']));
    }
}

/*
* setup the $auth_user object
*/
function init_auth_user()
{
    global $auth_user, $dbh;

    if (empty($_COOKIE['PEAR_USER']) || empty($_COOKIE['PEAR_PW'])) {
        $auth_user = null;
        return false;
    }
    if (!empty($auth_user)) {
        return true;
    }

    $data = user::info($_COOKIE['PEAR_USER'], null, true, false);
    $auth_user = new PEAR_Auth();
    $auth_user->data($data);
    switch (strlen(@$auth_user->password)) {
        // handle old-style DES-encrypted passwords
        case 13: {
            $seed = substr($auth_user->password, 0, 2);
            if (crypt($_COOKIE['PEAR_PW'], $seed) == @$auth_user->password) {
                return true;
            }
            break;
        }
        // handle new-style MD5-encrypted passwords
        case 32: {
            if (md5($_COOKIE['PEAR_PW']) == @$auth_user->password) {
                return true;
            }
            break;
        }
    }
    $auth_user = null;
    return false;
}

class PEAR_Auth
{
    function data($data)
    {
        if (!is_array($data)) {
            return;
        }
        foreach ($data as $k => $d) {
            $this->{$k} = $d;
        }
    }

    function is($handle)
    {
        global $auth_user;

        if (isset($auth_user) && $auth_user) {
            $ret = strtolower($auth_user->handle);
        } elseif (isset($this->handle)) {
            $ret = strtolower($this->handle);
        } else {
            return false;
        }
        return (strtolower($handle) == $ret);
    }

    function isAdmin()
    {
        if (!isset($this->handle)) {
            return false;
        }
        return (user::isAdmin($this->handle));
    }

    function isQA()
    {
        if (!isset($this->handle)) {
            return false;
        }
        return user::isQA($this->handle);
    }

    /**
     * Generate link for user
     *
     * @access public
     * @return string
     */
    function makeLink()
    {
        if (!isset($this->handle) || !isset($this->name)) {
            throw new Exception('Programmer error: please report to pear-dev@lists.php.net.' .
                ' $auth_user not initialized with data()');
        }
        return '<a href="/user/' . $this->handle . '/">' . htmlspecialchars($this->name)
            . '</a>';
    }
}
