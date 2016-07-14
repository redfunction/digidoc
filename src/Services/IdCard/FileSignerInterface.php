<?php
namespace Bigbank\DigiDoc\Services\IdCard;

/**
 * Put files into a .bdoc container and sign them with ID Card
 */
interface FileSignerInterface
{

    /**
     * Start adding signature with ID Card
     *
     * @param        $certificate
     * @param string $tokenId
     * @param string $role
     * @param string $city
     * @param string $state
     * @param string $postalCode
     * @param string $country
     * @param string $signingProfile
     *
     * @return mixed
     */
    public function PrepareSignature($certificate, $tokenId='', $role='', $city='', $state='',
                                     $postalCode='', $country='', $signingProfile='');

    /**
     * Finalize the signature adding that was previously started with PrepareSignature
     *
     * @param $signatureId
     * @param $signatureValue
     *
     * @return mixed
     */
    public function FinalizeSignature($signatureId, $signatureValue);

    /**
     * Returns OK only if all the signatures on the document are valid
     *
     * @return mixed
     */
    public function getSignatureStatus();
}
