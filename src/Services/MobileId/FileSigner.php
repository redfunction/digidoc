<?php
namespace Bigbank\DigiDoc\Services\MobileId;

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
    public function waitForSignature(callable $callback)
    {

        $status = InteractionStatus::OUTSTANDING_TRANSACTION;
        while ($status == InteractionStatus::OUTSTANDING_TRANSACTION) {
            $status = $this->askStatus();
            sleep($this->pollingFrequency);
        }

        $fileData = $status === InteractionStatus::SIGNATURE ? $this->downloadContainer() : [];
        return call_user_func($callback, $status, $fileData, $this->sessionCode);
    }

    /**
     * {@inheritdoc}
     */
    public function sign($idCode, $phoneNumber, $serviceName, $messageToDisplay)
    {

        $response = $this->digiDocService->MobileSign(
            $this->sessionCode,
            $idCode,
            'EE',
            $phoneNumber,
            $serviceName,
            $messageToDisplay,
            'EST',
            '',
            '',
            '',
            '',
            '',
            '',
            'asynchClientServer',
            null,
            false,
            false
        );

        $response['Sesscode'] = $this->sessionCode;
        return $response;
    }
}
