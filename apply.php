<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;


/*
|--------------------------------------------------------------------------
| LOAD COMPOSER
|--------------------------------------------------------------------------
*/

require dirname(__DIR__, 1) . '/vendor/autoload.php';


/*
|--------------------------------------------------------------------------
| LOAD ENVIRONMENT VARIABLES
|--------------------------------------------------------------------------
*/

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 1));
$dotenv->load();


/*
|--------------------------------------------------------------------------
| SMTP CONFIGURATION
|--------------------------------------------------------------------------
*/

$smtpHost = $_ENV['SMTP_HOST'] ?? '';
$smtpPort = (int)($_ENV['SMTP_PORT'] ?? 587);
$smtpUser = $_ENV['SMTP_USERNAME'] ?? '';
$smtpPass = $_ENV['SMTP_PASSWORD'] ?? '';

$senderEmail = $_ENV['MAIL_FROM'] ?? 'sobhainnovationscout@gmail.com';
$senderName  = $_ENV['MAIL_FROM_NAME'] ?? 'Sobha Innovation Scout CEE';

$recipientEmail = $_ENV['MAIL_TO'] ?? 'projekty@innteo.pl';


/*
|--------------------------------------------------------------------------
| BASIC CONFIGURATION VALIDATION
|--------------------------------------------------------------------------
*/

if (
    $smtpHost === '' ||
    $smtpUser === '' ||
    $smtpPass === ''
) {
    error_log('SMTP configuration is incomplete.');

    http_response_code(500);

    exit('The application system is temporarily unavailable.');
}


/*
|--------------------------------------------------------------------------
| FILE CONFIGURATION
|--------------------------------------------------------------------------
*/

$maxFileSize = 20 * 1024 * 1024; // 10 MB


$allowedExtensions = [
    'pdf',
    'pptx',
    'jpg',
    'jpeg',
    'png'
];


$allowedMimeTypes = [

    'pdf' => [
        'application/pdf'
    ],

    'pptx' => [
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip'
    ],

    'jpg' => [
        'image/jpeg'
    ],

    'jpeg' => [
        'image/jpeg'
    ],

    'png' => [
        'image/png'
    ]

];


/*
|--------------------------------------------------------------------------
| ONLY POST REQUESTS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    exit('Method Not Allowed');
}


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function postValue(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}


/**
 * Prepare one value for a semicolon-delimited Excel/CSV row.
 * Quotes and new lines are preserved according to CSV rules.
 */
function excelCsvValue(string $value): string
{
    return '"' . str_replace('"', '""', $value) . '"';
}


function showMessage(
    string $title,
    string $message,
    bool $success = false
): never {

    $accent = $success
        ? '#b8903f'
        : '#0f2742';


    echo '<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>'
        . htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        )
        . '</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <style>

        .form-result {

            min-height:60vh;

            display:flex;

            align-items:center;

            justify-content:center;

            padding:80px 20px;

            background:#fafaf8;

        }


        .form-result-box {

            width:100%;

            max-width:720px;

            padding:55px 50px;

            text-align:center;

            background:#fff;

            border-top:4px solid '
            . $accent .
            ';

            box-shadow:
                0 18px 45px rgba(0,0,0,.08);

        }


        .form-result-box h1 {

            margin:0 0 20px;

            color:#0f2742;

            font-size:2rem;

        }


        .form-result-box p {

            max-width:560px;

            margin:0 auto 30px;

            color:#555;

            line-height:1.7;

        }


        .form-result-icon {

            width:58px;

            height:58px;

            margin:0 auto 25px;

            border:2px solid '
            . $accent .
            ';

            border-radius:50%;

            display:flex;

            align-items:center;

            justify-content:center;

            color:'
            . $accent .
            ';

            font-size:28px;

        }


        .form-result-box .btn {

            display:inline-block;

        }


        @media(max-width:600px){

            .form-result-box{

                padding:40px 25px;

            }


            .form-result-box h1{

                font-size:1.7rem;

            }

        }

    </style>

</head>


<body>


<header>

    <div class="container nav">

        <div class="logo">

            <a href="index.html">
                Sobha Innovation Scout – Poland & CEE
            </a>

        </div>


        <nav>

            <ul>

                <li>
                    <a href="index.html">
                        Home
                    </a>
                </li>

                <li>
                    <a href="apply.html">
                        Apply
                    </a>
                </li>

                <li>
                    <a href="about.html">
                        About Us
                    </a>
                </li>

                <li>
                    <a href="regulations.html">
                        Regulations
                    </a>
                </li>

            </ul>

        </nav>

    </div>

</header>


<main class="form-result">

    <div class="form-result-box">

        <div class="form-result-icon">'
            . ($success ? '✓' : '!')
            . '</div>


        <h1>'
            . htmlspecialchars(
                $title,
                ENT_QUOTES,
                'UTF-8'
            )
            . '</h1>


        <p>'
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . '</p>


        <a
            href="index.html"
            class="btn"
        >
            Back to Homepage
        </a>

    </div>

</main>

</body>

</html>';

    exit;
}


function fail(string $message): never
{
    showMessage(
        'Application Not Submitted',
        $message,
        false
    );
}


/*
|--------------------------------------------------------------------------
| ANTI-SPAM HONEYPOT
|--------------------------------------------------------------------------
*/

if (
    !empty(
        $_POST['website_check'] ?? ''
    )
) {

    fail(
        'Your application could not be submitted.'
    );
}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$company         = postValue('company');
$contact         = postValue('contact');
$email           = postValue('email');
$phone           = postValue('phone');
$website         = postValue('website');
$sector          = postValue('sector');
$description     = postValue('description');
$team            = postValue('team');
$challenges      = postValue('challenges');
$differentiation = postValue('differentiation');
$cooperation     = postValue('cooperation');
$pilot           = postValue('pilot');
$impact          = postValue('impact');


/*
|--------------------------------------------------------------------------
| REQUIRED FIELDS
|--------------------------------------------------------------------------
*/

$requiredFields = [

    'Company Name'
        => $company,

    'Contact Person\'s Full Name'
        => $contact,

    'Email Address'
        => $email,

    'Phone Number'
        => $phone,

    'Company Website or LinkedIn Page'
        => $website,

    'Sector / Industry'
        => $sector,

    'Company Description'
        => $description,

    'Founder and Team Information'
        => $team,

    'Technological / Business Challenges'
        => $challenges,

    'Competitive Differentiation'
        => $differentiation,

    'Potential Cooperation with Sobha'
        => $cooperation,

    'Proposed Use Case / Pilot Project'
        => $pilot,

    'Expected Business / Operational Impact'
        => $impact,

];


foreach (
    $requiredFields
    as $fieldName => $value
) {

    if ($value === '') {

        fail(
            'Please complete the following field: '
            . $fieldName
        );

    }

}


/*
|--------------------------------------------------------------------------
| LENGTH VALIDATION
|--------------------------------------------------------------------------
*/

$lengthLimits = [

    'Company Name'
        => [$company, 200],

    'Contact Person'
        => [$contact, 150],

    'Phone Number'
        => [$phone, 30],

    'Sector / Industry'
        => [$sector, 150],

    'Company Description'
        => [$description, 5000],

    'Founder and Team Information'
        => [$team, 5000],

    'Challenges'
        => [$challenges, 5000],

    'Differentiation'
        => [$differentiation, 5000],

    'Cooperation'
        => [$cooperation, 5000],

    'Pilot Project'
        => [$pilot, 6000],

    'Expected Impact'
        => [$impact, 6000],

];


foreach (
    $lengthLimits
    as $fieldName => [$value, $maxLength]
) {

    if (
        mb_strlen($value)
        > $maxLength
    ) {

        fail(
            $fieldName
            . ' is too long.'
        );

    }

}


/*
|--------------------------------------------------------------------------
| EMAIL VALIDATION
|--------------------------------------------------------------------------
*/

if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    fail(
        'Please provide a valid email address.'
    );

}


/*
|--------------------------------------------------------------------------
| WEBSITE VALIDATION
|--------------------------------------------------------------------------
*/

if (
    !filter_var(
        $website,
        FILTER_VALIDATE_URL
    )
) {

    fail(
        'Please provide a valid company website or LinkedIn URL.'
    );

}


/*
|--------------------------------------------------------------------------
| GDPR CONSENT
|--------------------------------------------------------------------------
*/

if (
    empty($_POST['consent'])
    ||
    $_POST['consent'] !== '1'
) {

    fail(
        'Please accept the personal data processing consent.'
    );

}


/*
|--------------------------------------------------------------------------
| FILE VALIDATION
|--------------------------------------------------------------------------
*/

if (
    !isset($_FILES['attachment'])
    ||
    !is_array($_FILES['attachment'])
) {

    fail(
        'Please attach your company presentation or brief.'
    );

}


$file = $_FILES['attachment'];


if (
    $file['error']
    !== UPLOAD_ERR_OK
) {

    switch ($file['error']) {

        case UPLOAD_ERR_INI_SIZE:

        case UPLOAD_ERR_FORM_SIZE:

            fail(
                'The uploaded file is too large.'
            );


        case UPLOAD_ERR_NO_FILE:

            fail(
                'Please attach your company presentation or brief.'
            );


        default:

            fail(
                'There was an error uploading your file.'
            );

    }

}


/*
|--------------------------------------------------------------------------
| FILE SIZE
|--------------------------------------------------------------------------
*/

if (
    (int)$file['size']
    > $maxFileSize
) {

    fail(
        'The attached file cannot exceed 20 MB.'
    );

}


if (
    (int)$file['size']
    <= 0
) {

    fail(
        'The uploaded file is empty.'
    );

}


/*
|--------------------------------------------------------------------------
| FILE EXTENSION
|--------------------------------------------------------------------------
*/

$originalFileName =
    basename(
        (string)$file['name']
    );


$extension =
    strtolower(
        pathinfo(
            $originalFileName,
            PATHINFO_EXTENSION
        )
    );


if (
    !in_array(
        $extension,
        $allowedExtensions,
        true
    )
) {

    fail(
        'Invalid file format. Accepted formats are PDF, PPTX, JPG and PNG.'
    );

}


/*
|--------------------------------------------------------------------------
| REAL MIME TYPE
|--------------------------------------------------------------------------
*/

$finfo =
    new finfo(
        FILEINFO_MIME_TYPE
    );


$mimeType =
    $finfo->file(
        $file['tmp_name']
    );


if (
    !isset(
        $allowedMimeTypes[$extension]
    )
    ||
    !in_array(
        $mimeType,
        $allowedMimeTypes[$extension],
        true
    )
) {

    fail(
        'The uploaded file does not match its declared file type.'
    );

}


/*
|--------------------------------------------------------------------------
| CREATE PHPMailer
|--------------------------------------------------------------------------
*/

$mail = new PHPMailer(true);


try {

    /*
    |--------------------------------------------------------------------------
    | SMTP
    |--------------------------------------------------------------------------
    */

    $mail->isSMTP();

    $mail->Host =
        $smtpHost;

    $mail->SMTPAuth =
        true;

    $mail->Username =
        $smtpUser;

    $mail->Password =
        $smtpPass;

    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port =
        $smtpPort;


    /*
    |--------------------------------------------------------------------------
    | ENCODING
    |--------------------------------------------------------------------------
    */

    $mail->CharSet =
        'UTF-8';


    /*
    |--------------------------------------------------------------------------
    | SENDER
    |--------------------------------------------------------------------------
    */

    $mail->setFrom(
        $senderEmail,
        $senderName
    );


    /*
    |--------------------------------------------------------------------------
    | RECIPIENT
    |--------------------------------------------------------------------------
    */

    $mail->addAddress(
        $recipientEmail,
        'Sobha Innovation Scout'
    );


    /*
    |--------------------------------------------------------------------------
    | REPLY TO APPLICANT
    |--------------------------------------------------------------------------
    */

    $mail->addReplyTo(
        $email,
        $contact
    );


    /*
    |--------------------------------------------------------------------------
    | ATTACHMENT
    |--------------------------------------------------------------------------
    */

    $mail->addAttachment(
        $file['tmp_name'],
        $originalFileName
    );


    /*
    |--------------------------------------------------------------------------
    | SUBJECT
    |--------------------------------------------------------------------------
    */

    $mail->Subject =
        'New Sobha Innovation Scout Application – '
        . $company;


    /*
    |--------------------------------------------------------------------------
    | ESCAPED DATA FOR HTML
    |--------------------------------------------------------------------------
    */

    $safeCompany =
        htmlspecialchars(
            $company,
            ENT_QUOTES,
            'UTF-8'
        );


    $safeContact =
        htmlspecialchars(
            $contact,
            ENT_QUOTES,
            'UTF-8'
        );


    $safeEmail =
        htmlspecialchars(
            $email,
            ENT_QUOTES,
            'UTF-8'
        );


    $safePhone =
        htmlspecialchars(
            $phone,
            ENT_QUOTES,
            'UTF-8'
        );


    $safeWebsite =
        htmlspecialchars(
            $website,
            ENT_QUOTES,
            'UTF-8'
        );


    $safeSector =
        htmlspecialchars(
            $sector,
            ENT_QUOTES,
            'UTF-8'
        );


    $safeDescription =
        nl2br(
            htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
        );


    $safeTeam =
        nl2br(
            htmlspecialchars(
                $team,
                ENT_QUOTES,
                'UTF-8'
            )
        );


    $safeChallenges =
        nl2br(
            htmlspecialchars(
                $challenges,
                ENT_QUOTES,
                'UTF-8'
            )
        );


    $safeDifferentiation =
        nl2br(
            htmlspecialchars(
                $differentiation,
                ENT_QUOTES,
                'UTF-8'
            )
        );


    $safeCooperation =
        nl2br(
            htmlspecialchars(
                $cooperation,
                ENT_QUOTES,
                'UTF-8'
            )
        );


    $safePilot =
        nl2br(
            htmlspecialchars(
                $pilot,
                ENT_QUOTES,
                'UTF-8'
            )
        );


    $safeImpact =
        nl2br(
            htmlspecialchars(
                $impact,
                ENT_QUOTES,
                'UTF-8'
            )
        );


    $safeFileName =
        htmlspecialchars(
            $originalFileName,
            ENT_QUOTES,
            'UTF-8'
        );


    /*
    |--------------------------------------------------------------------------
    | EXCEL IMPORT DATA
    |--------------------------------------------------------------------------
    */

    $excelHeaders = [
        'Company Name',
        'Contact Person',
        'Email Address',
        'Phone Number',
        'Website / LinkedIn',
        'Sector / Industry',
        'Company Description',
        'Founder and Team Information',
        'Technological / Business Challenges',
        'Competitive Differentiation',
        'Potential Cooperation with Sobha',
        'Proposed Use Case / Pilot Project',
        'Expected Business / Operational Impact'
    ];

    $excelValues = [
        $company,
        $contact,
        $email,
        $phone,
        $website,
        $sector,
        $description,
        $team,
        $challenges,
        $differentiation,
        $cooperation,
        $pilot,
        $impact
    ];

    $excelHeaderRow = implode(';', array_map('excelCsvValue', $excelHeaders));
    $excelDataRow   = implode(';', array_map('excelCsvValue', $excelValues));

    $safeExcelHeaderRow = htmlspecialchars(
        $excelHeaderRow,
        ENT_QUOTES,
        'UTF-8'
    );

    $safeExcelDataRow = htmlspecialchars(
        $excelDataRow,
        ENT_QUOTES,
        'UTF-8'
    );


    /*
    |--------------------------------------------------------------------------
    | HTML EMAIL
    |--------------------------------------------------------------------------
    */

    $mail->isHTML(true);


    $mail->Body = <<<HTML

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#fafafa;color:#111111;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#fafafa;margin:0;padding:0;">
<tr>
<td align="center" style="padding:30px 15px;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:760px;background:#ffffff;border-top:4px solid #C8A53C;box-shadow:0 8px 24px rgba(0,0,0,.06);">

    <tr>
        <td style="padding:34px 35px 28px;background:#000000;color:#ffffff;">
            <div style="font-family:'Chronicle Display',Georgia,'Times New Roman',serif;font-size:27px;line-height:1.2;font-weight:700;color:#ffffff;">
                Sobha Innovation Scout CEE
            </div>
            <div style="margin-top:9px;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;letter-spacing:.08em;text-transform:uppercase;color:#C8A53C;">
                New Application
            </div>
        </td>
    </tr>

    <tr>
        <td style="padding:30px 35px 8px;">
            <p style="margin:0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:15px;line-height:1.75;color:#444444;">
                A new application has been submitted through the Sobha Innovation Scout CEE website.
            </p>
        </td>
    </tr>

    <tr>
        <td style="padding:20px 35px 10px;">
            <div style="font-family:'Chronicle Display',Georgia,'Times New Roman',serif;font-size:20px;line-height:1.3;font-weight:700;color:#000000;border-bottom:1px solid #dddddd;padding-bottom:10px;">
                Company Information
            </div>
            <p style="margin:18px 0 0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;color:#444444;"><strong style="font-weight:700;color:#111111;">Company Name</strong><br>$safeCompany</p>
            <p style="margin:14px 0 0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;color:#444444;"><strong style="font-weight:700;color:#111111;">Contact Person</strong><br>$safeContact</p>
            <p style="margin:14px 0 0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;color:#444444;"><strong style="font-weight:700;color:#111111;">Email Address</strong><br>$safeEmail</p>
            <p style="margin:14px 0 0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;color:#444444;"><strong style="font-weight:700;color:#111111;">Phone Number</strong><br>$safePhone</p>
            <p style="margin:14px 0 0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;color:#444444;"><strong style="font-weight:700;color:#111111;">Website / LinkedIn</strong><br>$safeWebsite</p>
            <p style="margin:14px 0 0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;color:#444444;"><strong style="font-weight:700;color:#111111;">Sector / Industry</strong><br>$safeSector</p>
        </td>
    </tr>

    <tr>
        <td style="padding:18px 35px 10px;">
            <div style="font-family:'Chronicle Display',Georgia,'Times New Roman',serif;font-size:20px;line-height:1.3;font-weight:700;color:#000000;border-bottom:1px solid #dddddd;padding-bottom:10px;">
                Company &amp; Team
            </div>
            <p style="margin:18px 0 0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.75;color:#444444;"><strong style="font-weight:700;color:#111111;">Company Description</strong><br>$safeDescription</p>
            <p style="margin:18px 0 0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.75;color:#444444;"><strong style="font-weight:700;color:#111111;">Founder and Team Information</strong><br>$safeTeam</p>
        </td>
    </tr>

    <tr>
        <td style="padding:18px 35px 10px;">
            <div style="font-family:'Chronicle Display',Georgia,'Times New Roman',serif;font-size:20px;line-height:1.3;font-weight:700;color:#000000;border-bottom:1px solid #dddddd;padding-bottom:10px;">
                Business &amp; Technology
            </div>
            <p style="margin:18px 0 0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.75;color:#444444;"><strong style="font-weight:700;color:#111111;">Challenges Addressed</strong><br>$safeChallenges</p>
            <p style="margin:18px 0 0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.75;color:#444444;"><strong style="font-weight:700;color:#111111;">Competitive Differentiation</strong><br>$safeDifferentiation</p>
        </td>
    </tr>

    <tr>
        <td style="padding:18px 35px 10px;">
            <div style="font-family:'Chronicle Display',Georgia,'Times New Roman',serif;font-size:20px;line-height:1.3;font-weight:700;color:#000000;border-bottom:1px solid #dddddd;padding-bottom:10px;">
                Potential Cooperation with Sobha
            </div>
            <p style="margin:18px 0 0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.75;color:#444444;"><strong style="font-weight:700;color:#111111;">Potential Cooperation</strong><br>$safeCooperation</p>
            <p style="margin:18px 0 0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.75;color:#444444;"><strong style="font-weight:700;color:#111111;">Proposed Use Case / Pilot Project</strong><br>$safePilot</p>
            <p style="margin:18px 0 0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.75;color:#444444;"><strong style="font-weight:700;color:#111111;">Expected Business / Operational Impact</strong><br>$safeImpact</p>
        </td>
    </tr>

    <tr>
        <td style="padding:20px 35px 25px;">
            <div style="font-family:'Chronicle Display',Georgia,'Times New Roman',serif;font-size:20px;line-height:1.3;font-weight:700;color:#000000;border-bottom:1px solid #dddddd;padding-bottom:10px;">
                Excel Import
            </div>
            <p style="margin:15px 0 10px;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;color:#555555;">
                The two rows below are semicolon-delimited CSV data. The first row contains column names and the second row contains the submitted text responses. Values are quoted so semicolons and line breaks inside answers remain importable in Excel.
            </p>
            <div style="padding:15px;background:#f7f5ef;border-left:4px solid #C8A53C;overflow-wrap:anywhere;word-break:break-word;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.6;color:#222222;">
                <div style="margin-bottom:12px;"><strong>HEADER</strong><br><code style="font-family:Consolas,'Courier New',monospace;">$safeExcelHeaderRow</code></div>
                <div><strong>DATA</strong><br><code style="font-family:Consolas,'Courier New',monospace;">$safeExcelDataRow</code></div>
            </div>
        </td>
    </tr>

    <tr>
        <td style="margin:0 35px 30px;padding:18px 20px;background:#f7f5ef;border-left:4px solid #C8A53C;">
            <strong style="font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;color:#111111;">Attachment</strong>
            <div style="margin-top:7px;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#444444;">$safeFileName</div>
        </td>
    </tr>

    <tr>
        <td style="padding:22px 35px;background:#000000;color:#ffffff;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:12px;line-height:1.7;">
            Sobha Innovation Scout CEE<br>
            Application submitted via innteo.pl
        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>

HTML;


    /*
    |--------------------------------------------------------------------------
    | PLAIN TEXT EMAIL
    |--------------------------------------------------------------------------
    */

    $mail->AltBody =

        "New Sobha Innovation Scout CEE Application\n\n"

        . "COMPANY INFORMATION\n"

        . "Company Name: "
        . $company
        . "\n"

        . "Contact Person: "
        . $contact
        . "\n"

        . "Email: "
        . $email
        . "\n"

        . "Phone: "
        . $phone
        . "\n"

        . "Website / LinkedIn: "
        . $website
        . "\n"

        . "Sector / Industry: "
        . $sector
        . "\n\n"


        . "COMPANY & TEAM\n"

        . "Company Description:\n"
        . $description
        . "\n\n"

        . "Founder and Team Information:\n"
        . $team
        . "\n\n"


        . "BUSINESS & TECHNOLOGY\n"

        . "Challenges Addressed:\n"
        . $challenges
        . "\n\n"

        . "Competitive Differentiation:\n"
        . $differentiation
        . "\n\n"


        . "COOPERATION WITH SOBHA\n"

        . "Potential Cooperation:\n"
        . $cooperation
        . "\n\n"

        . "Proposed Use Case / Pilot:\n"
        . $pilot
        . "\n\n"

        . "Expected Impact within 6–12 Months:\n"
        . $impact
        . "\n\n"


        . "Attachment: "
        . $originalFileName

        . "\n\nEXCEL IMPORT (semicolon-delimited)\n"
        . $excelHeaderRow
        . "\n"
        . $excelDataRow;


    /*
|--------------------------------------------------------------------------
| SEND APPLICATION EMAIL TO SOBHA / INNTEO
|--------------------------------------------------------------------------
*/

$mail->send();


/*
|--------------------------------------------------------------------------
| SEND CONFIRMATION EMAIL TO APPLICANT
|--------------------------------------------------------------------------
*/

try {

    $confirmationMail = new PHPMailer(true);

    $confirmationMail->isSMTP();

    $confirmationMail->Host =
        $smtpHost;

    $confirmationMail->SMTPAuth =
        true;

    $confirmationMail->Username =
        $smtpUser;

    $confirmationMail->Password =
        $smtpPass;

    $confirmationMail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;

    $confirmationMail->Port =
        $smtpPort;

    $confirmationMail->CharSet =
        'UTF-8';


    /*
    |--------------------------------------------------------------------------
    | SENDER
    |--------------------------------------------------------------------------
    */

    $confirmationMail->setFrom(
        $senderEmail,
        $senderName
    );


    /*
    |--------------------------------------------------------------------------
    | APPLICANT
    |--------------------------------------------------------------------------
    */

    $confirmationMail->addAddress(
        $email,
        $contact
    );


    /*
    |--------------------------------------------------------------------------
    | SUBJECT
    |--------------------------------------------------------------------------
    */

    $confirmationMail->Subject =
        'Thank you for applying – Sobha Innovation Scout CEE';


    /*
    |--------------------------------------------------------------------------
    | HTML
    |--------------------------------------------------------------------------
    */

    $confirmationMail->isHTML(true);

    $confirmationMail->Body = <<<HTML

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#fafafa;color:#111111;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#fafafa;margin:0;padding:0;">
<tr>
<td align="center" style="padding:30px 15px;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:700px;background:#ffffff;border-top:4px solid #C8A53C;box-shadow:0 8px 24px rgba(0,0,0,.06);">

    <tr>
        <td style="padding:34px 35px 28px;background:#000000;color:#ffffff;">
            <div style="font-family:'Chronicle Display',Georgia,'Times New Roman',serif;font-size:27px;line-height:1.2;font-weight:700;color:#ffffff;">
                Sobha Innovation Scout CEE
            </div>
            <div style="margin-top:9px;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;letter-spacing:.08em;text-transform:uppercase;color:#C8A53C;">
                Application Confirmation
            </div>
        </td>
    </tr>

    <tr>
        <td style="padding:38px 35px 32px;">
            <div style="width:52px;height:52px;border:1px solid #C8A53C;border-radius:50%;text-align:center;line-height:52px;font-family:Georgia,'Times New Roman',serif;font-size:24px;color:#C8A53C;margin-bottom:25px;">
                ✓
            </div>

            <p style="margin:0 0 18px;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:15px;line-height:1.75;color:#444444;">
                Dear {$safeContact},
            </p>

            <h1 style="margin:0 0 20px;font-family:'Chronicle Display',Georgia,'Times New Roman',serif;font-size:30px;line-height:1.2;font-weight:700;color:#000000;">
                Thank you for applying.
            </h1>

            <p style="margin:0 0 18px;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:15px;line-height:1.8;color:#444444;">
                Thank you for applying to the <strong style="font-weight:700;color:#111111;">Sobha Innovation Scout – Poland &amp; CEE.</strong>
            </p>

            <p style="margin:0 0 18px;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:15px;line-height:1.8;color:#444444;">
                We confirm that your application has been successfully received by our team.
            </p>

            <p style="margin:0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:15px;line-height:1.8;color:#444444;">
                Our team will review the information provided and contact you regarding the next stage of the process.
            </p>

            <div style="margin-top:32px;padding-top:22px;border-top:1px solid #e5e5e5;">
                <p style="margin:0;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;color:#444444;">
                    Best regards,<br>
                    <strong style="font-weight:700;color:#111111;">Sobha Innovation Scout CEE</strong>
                </p>
            </div>
        </td>
    </tr>

    <tr>
        <td style="padding:22px 35px;background:#000000;color:#ffffff;font-family:'Ringside Regular',Arial,Helvetica,sans-serif;font-size:12px;line-height:1.7;">
            Sobha Innovation Scout CEE<br>
            Powered by Innteo
        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>

HTML;


    /*
    |--------------------------------------------------------------------------
    | PLAIN TEXT
    |--------------------------------------------------------------------------
    */

    $confirmationMail->AltBody =

        "Dear "
        . $contact
        . ",\n\n"

        . "Thank you for applying to the Sobha Innovation Scout – Poland & CEE.\n\n"

        . "We confirm that your application has been successfully received by our team.\n\n"

        . "Our team will review the information provided and contact you regarding the next stage of the process.\n\n"

        . "Best regards,\n"
        . "Sobha Innovation Scout CEE";


    /*
    |--------------------------------------------------------------------------
    | SEND
    |--------------------------------------------------------------------------
    */

    $confirmationMail->send();


} catch (Exception $confirmationException) {

    /*
    | We don't reject the application if the confirmation
    | email fails. The application itself was already sent.
    */

    error_log(
        'Applicant confirmation email error: '
        . $confirmationMail->ErrorInfo
    );

}


} catch (Exception $e) {

    error_log(
        'Sobha application email error: '
        . $mail->ErrorInfo
    );


    fail(
        'We could not send your application. Please try again later or contact us directly.'
    );

}


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

showMessage(

    'Application Successfully Submitted',

    'Thank you for applying to the Sobha Innovation Scout – Poland & CEE. Your application and attachment have been successfully received. Our team will review the information provided and contact you regarding the next stage of the process.',

    true

);