<?php
if (!isset($_GET['handle'])) {
    response_header('Error: no handle selected');
    report_error('Error: no handle selected for display');
    response_footer();
    exit;
}
require 'bugs/pear-bug-accountrequest.php';
$account = new PEAR_Bug_Accountrequest($_GET['handle']);
if ($account->pending()) {
    try {
        $account->sendEmail();
    } catch (Exception $e) {
        response_header('Error: cannot send confirmation email');
        report_error('Error: confirmation email could not be sent: ' . $e->getMessage());
        response_footer();
        exit;
    }
} else {
    response_header('Error: handle does not need verification');
    report_error('Error: handle is either already verified or does not exist');
    response_footer();
    exit;
}
response_header('PEAR :: email re-sent');?>
<h1>Verification email resent</h1>
<?php
response_footer();