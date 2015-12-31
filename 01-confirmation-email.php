<?php
/*
 * Portion of code from an online student application system utilizing 
 * the Swift Mailer library where references are immediately notified 
 * through email of a student’s application submission. 
 *
 * PHP version 5
 * 
 * @author     Christine Kozin <ckozin@gmail.com>
 * @copyright  2013 Questar III BOCES
*/


// Use Swift Mailer library
require_once '../lib/swift_required.php';
$appID      = $dbh->lastInsertId('id');
$orgURL     = 'organization URL';

// Student data email, name
$sEmail     = $insData['sEmail'];
$sFName     = $insData['sFName'];
$sLName     = $insData['sLName'];
$sName      = $sFName . " " . $sLName;

// First reference info
$refName1   = $insData['refTName'];
$refEmail1  = $insData['refTEmail'];
$refName2   = $insData['refCounselorName'];
$refEmail2  = $insData['refCounselorEmail'];

// Second reference
$refName3   = $insData['refCommEmpName'];
$refEmail3  = $insData['refCommEmpEmail'];

// Get coourse information and set variable			
$courseName = $insData['pID'];
switch ($courseName) {
    case "1":
        $courseName     = "Course name 1";
        $programWebsite = "URL";
        break;
    case "2":
        $courseName     = "Course name 2";
        $programWebsite = "URL";
        break;
    case "3":
        $courseName     = "Course name 3";
        $programWebsite = "URL";
        break;
    case "4":
        $courseName     = "Course name 4";
        $programWebsite = "URL";
        break;
    case "5":
        $courseName     = "Course name 5";
        $programWebsite = "URL";
        break;
}

// Set subject header for student email
$appSubject   = "Program Application";

// Set subject header for reference email
$refSubject   = "Reference request for Program Application";

//Set body for student html email
$appBody      = "<p>Dear " . $sFName . ", </p><p>Your application for " . $courseName . " program has been received.</p>";

// Set alternate body for text email
$altappBody   = "Dear " . $sFName . ", \n\nYour application for " . $courseName . " program has been received.\n";

// Set reference email email
$refBody      = "<p>Dear {name},</p><p>" . $sFName . " " . $sLName . " has requested that you serve as a reference for his/her application to the " . $courseName . " program. Visit " . $programWebsite . " to learn more about the program.<p>Submit the online reference form at: <a href='" . $orgURL . "app=" . $appID . "'>" . $orgURL . "app=." . $appID . "</a></p>";

// Set alternate reference email
$altrefBody   = "Dear {name},\n\n" . $sFName . " " . $sLName . " has requested that you serve as a reference for his/her application to the " . $courseName . " program. Visit " . $programWebsite . " to learn more about the program.\n\nSubmit the online reference form  at: <a href='" . $orgURL . "app=" . $appID . "'>" . $orgURL . "app=." . $appID . "</a> \n\n";

// Create array for emails to student and references
$email_array  = array(
    array(
        "email" => $sEmail,
        "name" => $sName,
        "subject" => $appSubject,
        "Body" => $appBody,
        "altBody" => $altappBody
    ),
    array(
        "email" => $refEmail1,
        "name" => $refName1,
        "subject" => $refSubject,
        "Body" => $refBody,
        "altBody" => $altrefBody
    ),
    array(
        "email" => $refEmail2,
        "name" => $refName2,
        "subject" => $refSubject,
        "Body" => $refBody,
        "altBody" => $altrefBody
    ),
    array(
        "email" => $refEmail3,
        "name" => $refName3,
        "subject" => $refSubject,
        "Body" => $refBody,
        "altBody" => $altrefBody
    )
);
$replacements = array();

// Loop through each email to be sent
foreach ($email_array as $this_email) {
    $replacements[$this_email["email"]] = array(
        "{name}" => $this_email["name"]
    );
    $transport                          = Swift_SmtpTransport::newInstance('localhost', 25)->setUsername('username')->setPassword('password');
    
    // Create an instance of the Swift Mailer plugin and register it
    $plugin                             = new Swift_Plugins_DecoratorPlugin($replacements);
    $mailer                             = Swift_Mailer::newInstance($transport);
    $mailer->registerPlugin($plugin);
    
    // Create the message
    $message = Swift_Message::newInstance();
    $message->setFrom("Email address", "Name");
    $message->AddReplyTo("reply-to addres", "Name");
    $message->setMaxLineLength(900); // set word wrap to 900 characters
    $message->setSubject($this_email["subject"]);
    $message->setBody($this_email["Body"], 'text/html');
    $message->addPart($this_email["altBody"], 'text/plain');
    $message->setReturnPath('Bounce back email');
    $message->setTo(array(
        $this_email["email"] => $this_email["name"]
    ));
    $mailer->send($message);
}

// Redirect to homepage
header(sprintf("Location: %s", $orgURL));
?>