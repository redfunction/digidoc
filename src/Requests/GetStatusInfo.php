<?php
namespace Bigbank\DigiDoc\Requests;

/**
 * Poll the status of the signing
 */
class GetStatusInfo extends AbstractRequest
{

    /**
     * {@inheritdoc}
     */
    public function getDefaultArguments()
    {

        return [
            'Sesscode'      => null,
            'ReturnDocInfo' => false,
            'WaitSignature' => false,
        ];
    }
}