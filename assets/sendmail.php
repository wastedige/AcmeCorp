<?php
session_start();

require '/vendor/autoload.php';

// solving issues with curl-error-60
// https://laracasts.com/discuss/channels/general-discussion/curl-error-60-ssl-certificate-problem-unable-to-get-local-issuer-certificate?page=1
// deeper xdebug dump
// http://stackoverflow.com/questions/9998490/how-to-get-xdebug-var-dump-to-show-full-object-array
use SparkPost\SparkPost;
use GuzzleHttp\Client;
use Ivory\HttpAdapter\Guzzle6HttpAdapter;
//  $httpAdapter = new Guzzle6HttpAdapter(new Client());
$httpAdapter = new Guzzle6HttpAdapter(new Client(['verify' => getenv('ProgramFiles(x86)') . '\Git\bin\curl-ca-bundle.crt']));
$sparky = new SparkPost($httpAdapter, ['key'=>'xxxxxxxxxxxxxxxxxxxxxx']);

// Email address verification
function isEmail($email) {
    return(preg_match("/^[-_.[:alnum:]]+@((([[:alnum:]]|[[:alnum:]][[:alnum:]-]*[[:alnum:]])\.)+(ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|in|info|int|io|iq|ir|is|it|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)$|(([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))$/i", $email));
}

if($_POST) {
    $clientName = strip_tags(addslashes(trim($_POST['name'])));
    $clientEmail = strip_tags(addslashes(trim($_POST['email'])));
    $subject = strip_tags(addslashes(trim($_POST['sendto']))) ;
    $message = strip_tags(addslashes(trim($_POST['message'])));
    $phone = strip_tags(addslashes(trim($_POST['phone'])));
    $company = strip_tags(addslashes(trim($_POST['company'])));
    $usercaptcha = strip_tags(addslashes(trim($_POST['usercaptcha'])));

    $array = array();
    $array['nameMessage'] = '';
    $array['emailMessage'] = '';
    $array['messageMessage'] = '';
    $array['phoneMessage'] = '';
    $array['captchaMessage'] = '';

    if($clientName == '') {
        $array['nameMessage'] = 'Please enter your name.';
    }
    if(!isEmail($clientEmail)) {
        $array['emailMessage'] = 'Please insert a valid email address.';
    }
    if($message == '') {
        $array['messageMessage'] = 'Please enter your message.';
    }
    if($phone == '') {
        $array['phoneMessage'] = 'Please enter your phone.';
    }
    if ($usercaptcha == '') {
        $array['captchaMessage'] = 'Please enter captcha';
    } elseif (strtolower($_SESSION['captcha']) != strtolower($usercaptcha)) {
        $array['captchaMessage'] = ' *';
    }

    if($clientName != '' && isEmail($clientEmail) && $message != '' &&  $phone != '' && strtolower($_SESSION['captcha']) == strtolower($usercaptcha) ) {

      $mailcontent = $message . "<br><hr><br/>" .
          "Sent from: " . $clientEmail .
             " (Sender's IP: " . $_SERVER['REMOTE_ADDR'] . ") <br/>" .
          "Name: " . strip_tags(addslashes(trim($clientName))) . "<br/>" .
          "Phone: " . strip_tags(addslashes(trim($_POST['phone']))) . "<br/>" .
          "Company: " .  strip_tags(addslashes(trim($_POST['company']))) ;

      $mailarray = array();
      $mailarray['html'] = $mailcontent;
      $mailarray['from'] = 'ACME Inquiry <from@sparkpostbox.com>'; // sandbox mail -- for sending more than a few mails, use an actual account
      $mailarray['text'] = $mailcontent;
      $mailarray['subject'] = $subject;
      $mailarray['replyTo'] = $clientEmail;
      $mailarray['recipients'] = [
        [
          'address'=>[
            'name'=>'ACME',
            'email'=>'acme@acme.com'
          ]
        ]
      ];
      if(!empty($_FILES['attachment']['name'])) {
        $mailarray['attachments'] = [
          [
            'type'=>'application/pdf',
            'name'=>$_FILES['attachment']['name'],
            'data'=>base64_encode( file_get_contents($_FILES['attachment']['tmp_name']) )
          ]
        ];
      }

      try {
        $results = $sparky->transmission->send($mailarray);
        $ua=getBrowser();
        putInDB( $clientName, $phone, $clientEmail, $company, $message, $ua['name'], $ua['platform'], date("Y-m-d", time()) );
      } catch (\Exception $err) {
        echo 'Whoops! Something went wrong: ';
        var_dump($err);
      }
    }

    // this just reflects back the json containing error-messages
    // actual binding between json messages and fields happens in scripts.js
    header('Content-type: application/json');
    echo json_encode($array); // any other echos will cause the scripts.js parsing to fail
    exit;
}

function getBrowser()
{
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version= "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }

    // Next get the name of the useragent yes seperately and for good reason
    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
    {
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    }
    elseif(preg_match('/Firefox/i',$u_agent))
    {
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
    }
    elseif(preg_match('/Chrome/i',$u_agent))
    {
        $bname = 'Google Chrome';
        $ub = "Chrome";
    }
    elseif(preg_match('/Safari/i',$u_agent))
    {
        $bname = 'Apple Safari';
        $ub = "Safari";
    }
    elseif(preg_match('/Opera/i',$u_agent))
    {
        $bname = 'Opera';
        $ub = "Opera";
    }
    elseif(preg_match('/Netscape/i',$u_agent))
    {
        $bname = 'Netscape';
        $ub = "Netscape";
    }

    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }

    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
            $version= $matches['version'][0];
        }
        else {
            $version= $matches['version'][1];
        }
    }
    else {
        $version= $matches['version'][0];
    }

    // check if we have a number
    if ($version==null || $version=="") {$version="?";}

    return array(
        'userAgent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'pattern'    => $pattern
    );
}


function putInDB($name, $phone, $email, $company, $message, $browser, $os, $date) {

  // Database contact-address
  $servername = "localhost";
  $username = "root";
  $password = "";
  $dbname = "myAcmeDB";

  // Create connection
  $conn = new mysqli($servername, $username, $password);
  // Check connection
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }
  // echo "Connected successfully! ";
  // Create database
  $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
  if ($conn->query($sql) === TRUE) {
      // echo "Database created successfully! ";
  } else {
      // echo "Error creating database: " . $conn->error;
  }
  $conn->close();

  // Create connection to DB
  $conn = new mysqli($servername, $username, $password, $dbname);
  // Check connection
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }

  // sql to create table
  $sql = "CREATE TABLE IF NOT EXISTS AcmeClientInfo (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(70) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(50) NOT NULL,
    company VARCHAR(30),
    message VARCHAR(500) NOT NULL,
    browser VARCHAR(30),
    os VARCHAR(30),
    reg_date DATE
  )";

  if ($conn->query($sql) === TRUE) {
      // echo "Table created successfully";
  } else {
      // echo "Error creating table: " . $conn->error;
  }

  $sql = "INSERT INTO AcmeClientInfo (name, phone, email, company , message, browser, os, reg_date)
  VALUES ('$name', '$phone', '$email', '$company', '$message', '$browser', '$os', '$date')";

  if ($conn->query($sql) === TRUE) {
      // echo "New record created successfully";
  } else {
      // echo "Error: " . $sql . "<br>" . $conn->error;
  }
  $conn->close();
  return;
}


?>
