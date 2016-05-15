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

  $httpAdapter = new Guzzle6HttpAdapter(new Client(['verify' => getenv('ProgramFiles(x86)') . '\Git\bin\curl-ca-bundle.crt']));
  $sparky = new SparkPost($httpAdapter, ['key'=>'3058d5488843ff5772a336e7fbd78d15b778e44e']);



// Email address verification
function isEmail($email) {
    return(preg_match("/^[-_.[:alnum:]]+@((([[:alnum:]]|[[:alnum:]][[:alnum:]-]*[[:alnum:]])\.)+(ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|in|info|int|io|iq|ir|is|it|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)$|(([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))$/i", $email));
}




if($_POST) {
    // echo "<input type='text' disabled>";

    $clientName = addslashes(trim($_POST['name']));
    $clientEmail = addslashes(trim($_POST['email']));
    $subject = addslashes($_POST['sendto']) ;
    $message = addslashes(trim($_POST['message']));
    $phone = addslashes(trim($_POST['phone']));
    $usercaptcha = addslashes(trim($_POST['usercaptcha']));


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
        $array['captchaMessage'] = 'Please enter the captcha as shown';
    } elseif (strtolower($_SESSION['captcha']) != strtolower($usercaptcha)) {
        $array['captchaMessage'] = ' *';
    }

    if($clientName != '' && isEmail($clientEmail) && $message != '' &&  $phone != '' && strtolower($_SESSION['captcha']) == strtolower($usercaptcha) ) {

      $message = strip_tags(addslashes(trim($_POST['message']))) . "<br><hr><br/>" .
          "Sent from: " . $clientEmail .
             " (Sender's IP: " . $_SERVER['REMOTE_ADDR'] . ") <br/>" .
          "Name: " . strip_tags(addslashes(trim($clientName))) . "<br/>" .
          "Phone: " . strip_tags(addslashes(trim($_POST['phone']))) . "<br/>" .
          "Company: " .  strip_tags(addslashes(trim($_POST['company']))) ;

      $mailarray = array();
      $mailarray['html'] = $message;
      $mailarray['from'] = 'SysLogic Inquiry <no-reply@syslogicinc.com>';
      $mailarray['text'] = $message;
      $mailarray['subject'] = $subject;
      $mailarray['replyTo'] = $clientEmail;
      $mailarray['recipients'] = [
        [
          'address'=>[
            'name'=>'SysLogic Inquiry',
            'email'=>'shaahin@gmail.com'
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
      } catch (\Exception $err) {
        echo 'Whoops! Something went wrong';
        var_dump($err);
      }


    }
    // this just reflects back the json containing error-messages
    // actual binding between json messages and fields happens in scripts.js

    echo json_encode($array);
    exit;
}

?>
