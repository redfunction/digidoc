<?php

include __DIR__ . '/../../vendor/autoload.php';

use Bigbank\DigiDoc\DigiDoc;
use Bigbank\DigiDoc\Services\IdCard\FileSignerInterface;

// Instantiate the main class - use DigiDoc testing service
$digiDocService = new DigiDoc(DigiDoc::URL_TEST);

// Ask for the file signing service
/** @var FileSignerInterface $signer */
$signer = $digiDocService->getService(FileSignerInterface::class);

if(!empty($_POST["certificate"])) {
    $signer->setSessionCode($_POST["session"]);
    $data = $prepareSignatureResponse = $signer->prepareSignature($_POST['certificate']);
    header("Content-Type: application/json");
    print json_encode($data);
    exit;
}

if(!empty($_POST["signature"])) {
    $signer->setSessionCode($_POST["session"]);
    $data = $prepareSignatureResponse = $signer->finalizeSignature($_POST['signature_id'], $_POST['signature']);
    $signer->waitForSignature(function($status, $fileContent) use ($signer) {
        $content = base64_decode($fileContent);
        file_put_contents("testfile.bdoc", $content);
        $signer->closeSession();
    });
    header("Content-Type: application/json");
    print json_encode($data);
    exit;
}


print '<html><body>';

$bdocContent = '';
if (file_exists(__DIR__ . '/example.document.bdoc')) {
    $bdocContent = base64_encode(file_get_contents(__DIR__ . '/example.document.bdoc'));
} else {
    $fileContent = base64_encode(file_get_contents(__DIR__ . '/example.document.pdf'));
}

$signer->startSession($bdocContent);

printf("Uploading the file...\n");

if (empty($bdocContent) && !empty($fileContent)) {
    $signer->addFile('documet.pdf', 'application/pdf', $fileContent);
}

printf("Signing...\n");

print "<script src=\"hwcrypto.js\"></script>";
print "<script src=\"jquery.js\"></script>";
?>

<script>

    var lang = "et";
    var cert = "";

function log_text(msg) {
    console.log(msg);
}

function sign() {

    window.hwcrypto.getCertificate({lang: lang}).then(function(response) {
      var cert = response;
      log_text("Using certificate:\n" + hexToPem(response.hex));
      window.hwcrypto.sign(cert, {hex: hash}, {lang: lang}).then(function(response) {
        log_text("Generated signature:\n" + response.hex.match(/.{1,64}/g).join("\n"));
      }, function(err) {
        log_text("sign() failed: " + err);
      });
    }, function(err) {
      log_text("getCertificate() failed: " + err);
    });
}

function onSign(response) {
    var signature_id = response.SignatureId;
    window.hwcrypto.sign(cert, {type: 'SHA-256', hex: response.SignedInfoDigest}, {lang: lang}).then(function(response) {
        console.log("Generated signature:\n" + response.hex.match(/.{1,64}/g).join("\n"));
        console.log(response);
        $.post('', {session: "<?= $signer->getSession() ?>", signature: response.hex, signature_id: signature_id});
    }, function(err) {
        console.log(err);
    });
}

function prepareSignature(event) {
    window.hwcrypto.getCertificate({lang: lang}).then(function(response) {
        console.log(response);
      cert = response;
      $.post("", {certificate: response.hex, session: "<?= $signer->getSession() ?>"}, onSign, "json");
    }, function(err) {
      log_text("getCertificate() failed: " + err);
    });
}
</script>

    <button type="button" onclick="prepareSignature(event)">Sign</button>

</body></html>
