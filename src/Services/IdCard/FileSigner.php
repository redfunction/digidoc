<?php
namespace Bigbank\DigiDoc\Services\IdCard;

use Bigbank\DigiDoc\Services\AbstractFileSigner;
use Bigbank\DigiDoc\Soap\InteractionStatus;

/**
 * {@inheritdoc}
 */
class FileSigner extends AbstractFileSigner implements FileSignerInterface
{

    /**
     * {@inheritdoc}
     */
    public function prepareSignature(
        $certificate,
        $tokenId = '',
        $role = '',
        $city = '',
        $state = '',
        $postalCode = '',
        $country = '',
        $signingProfile = ''
    ) {
        return $this->digiDocService->PrepareSignature(
            $this->sessionCode,
            $certificate,
            $tokenId,
            $role,
            $city,
            $state,
            $postalCode,
            $country,
            $signingProfile
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeSignature($signatureId, $signatureValue)
    {
        return $this->digiDocService->FinalizeSignature(
            $this->sessionCode,
            $signatureId,
            $signatureValue
        );
    }

    /**
     * {@inheritdoc}
     */
    public function waitForSignature(callable $callback)
    {
        $status = $this->getSignatureStatus();
        while ($status == InteractionStatus::OUTSTANDING_TRANSACTION) {
            $status = $this->getSignatureStatus();
            sleep($this->pollingFrequency);
        }

        $fileData = $status === "OK" ? $this->downloadContainer() : [];
        return call_user_func($callback, $status, $fileData, $this->sessionCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getSignatureStatus()
    {
        $info = $this->digiDocService->GetSignedDocInfo($this->sessionCode);

        $signature = $info["SignedDocInfo"]->getSignatureInfo();
        if (is_array($signature)) {
            foreach ($signature as $signatureItem) {
                $status = $signatureItem->getStatus();
                if ($status != "OK") {
                    return $status;
                }
            }
        } else {
            $status = $signature->getStatus();
        }

        return $status;
    }
}
