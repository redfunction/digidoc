<html><body>
<pre>
    This example uses hwcrypto - https://github.com/open-eid/hwcrypto.js
    The sequence in all of this:
    1. Start a session
        1.1 if we want to add a signature to existing bdoc, then pass the base64 of bdoc to startSession
        1.2 if we want to create a new bdoc, then use addFile
        1.3 do not use 1.2 and 1.1 at the same time - you will probably get an error
    2. ask a certificate from Id Card with hwcrypto in browser
    3. send it to server with ajax and use prepareSignature
    4. use prepare signature output for hwcrypto input and ask for signature from Id Card with hwcrypto in browser
    5. send it to server with ajax and use finalizeSignature
    6. downloadContainer on server side
</pre>
<?php

// nuke the cache just in case
// there have been cases during development, when invalid wsdl was saved
ini_set('soap.wsdl_cache_enabled', '0');
ini_set('soap.wsdl_cache_ttl', '0');

include __DIR__ . '/../../vendor/autoload.php';

use Bigbank\DigiDoc\DigiDoc;
use Bigbank\DigiDoc\Services\IdCard\FileSignerInterface;

// Instantiate the main class - use DigiDoc testing service
$digiDocService = new DigiDoc(DigiDoc::URL_TEST);

// Ask for the file signing service
/** @var FileSignerInterface $signer */
$signer = $digiDocService->getService(FileSignerInterface::class);

/**
 * Here starts the ajax part.
 *
 * 1. Ask certificate from browser and store it in SK
 */
if(!empty($_POST['certificate'])) {
    $signer->setSessionCode($_POST['session']);
    $data = $prepareSignatureResponse = $signer->prepareSignature($_POST['certificate']);
    header('Content-Type: application/json');
    print json_encode($data);
    exit;
}

/**
 * 2. Finalize the signature from browser and store it in SK
 */
if(!empty($_POST['signature'])) {
    $signer->setSessionCode($_POST['session']);
    $data = $prepareSignatureResponse = $signer->finalizeSignature($_POST['signature_id'], $_POST['signature']);
    $signer->waitForSignature(function($status, $fileContent) use ($signer) {
        $content = base64_decode($fileContent);
        file_put_contents('testfile.bdoc', $content);
        $signer->closeSession();
    });
    header('Content-Type: application/json');
    print json_encode($data);
    exit;
}

$bdocContent = '';
if (file_exists(__DIR__ . '/example.document.bdoc')) {
    print 'We add new signature to existing BDOC';
    $bdocContent = base64_encode(file_get_contents(__DIR__ . '/example.document.bdoc'));
} else {
    print 'We create a new BDOC and add a signature to it';
    $fileContent = base64_encode(file_get_contents(__DIR__ . '/example.document.pdf'));
}

// the session has to be started, no matter what.
// if we are adding a new signature, then the base64 of bdoc must be passed here as well
$signer->startSession($bdocContent);

if (empty($bdocContent) && !empty($fileContent)) {
    // if we did not add new signature to existing bdoc, then we need to add a data file which we are going to sign
    $signer->addFile('documet.pdf', 'application/pdf', $fileContent);
}

?>
<p>
This is the frontend part of the signing
</p>

<script src='hwcrypto.js'></script>
<script src='https://code.jquery.com/jquery-3.1.0.min.js' integrity='sha256-cCueBR6CsyA4/9szpPfrX3s49M9vUU5BgtiJj06wt/s=' crossorigin='anonymous'></script>
<script>

    var lang = 'et';
    var cert = '';

function log_text(msg) {
    console.log(msg);
}

function onSign(response) {
    var signature_id = response.SignatureId;
    window.hwcrypto.sign(cert, {type: 'SHA-256', hex: response.SignedInfoDigest}, {lang: lang}).then(function(response) {
        console.log('Generated signature:\n' + response.hex.match(/.{1,64}/g).join('\n'));
        console.log(response);
        $.post('', {session: '<?= $signer->getSession() ?>', signature: response.hex, signature_id: signature_id});
    }, function(err) {
        console.log(err);
    });
}

function prepareSignature(event) {
    window.hwcrypto.getCertificate({lang: lang}).then(function(response) {
        console.log(response);
      cert = response;
      $.post('', {certificate: response.hex, session: '<?= $signer->getSession() ?>'}, onSign, 'json');
    }, function(err) {
      log_text('getCertificate() failed: ' + err);
    });
}
</script>

    <button type='button' onclick='prepareSignature(event)'>Click here, to start signing</button>

</body></html>
