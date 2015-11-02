<?php
namespace Bigbank\MobileId\Request;

/**
 * Start the two-step mobile ID signing process by sending a file
 */
class StartSession extends AbstractRequest
{

    /**
     * {@inheritdoc}
     */
    public function getDefaultArguments()
    {

        return [
            'SigningProfile' => '',
            'SigDocXML'      => '',
            'bHoldSession'   => true,
            'datafile'       => null
        ];
    }
}
