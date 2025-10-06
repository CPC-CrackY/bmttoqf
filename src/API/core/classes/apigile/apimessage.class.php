<?php

namespace EnedisLabBZH\Core\Apigile;

use EnedisLabBZH\Core\Apigile;
use EnedisLabBZH\Core\CoreException;

final class ApiMessage
{
    private Apigile $apigile;
    private bool $useForce = false;

    /**
     * __construct
     *
     * @param string $clientId Apigile credentials
     * @param string $clientSecret Apigile credentials
     * @param string $endpointApi url of the endpoint API
     * @param bool   $rawErrors true to display original error in case of exception.
     * @return void
     */
    public function __construct(string $clientId = null, string $clientSecret = null, string $endpointApi = null, bool $rawErrors = false)
    {
        $this->apigile = new Apigile($clientId, $clientSecret, $endpointApi, $rawErrors);
    }

    /**
     * checkCategory check if $category is valid
     *
     * @param  string $category
     * @return void
     */
    private function checkCategory($category)
    {
        if (!$category || !in_array($category, [
            'ENEDIS_incident',
            'Enedis_ComClient',
            'Enedis_interne',
            'Enedis_Prev_Travx_BI',
            'Enedis_RDV',
            'Enedis_Releve',
            'Enedis_Linky',
            'Enedis-Generale',
        ])) {
            throw new CoreException("invalid category: {$category}");
        }
    }

    /**
     * checkIssuer check if $issuer is valid
     *
     * @param  string $issuer
     * @return void
     */
    private function checkIssuer($issuer)
    {
        $domain = filter_var($issuer, FILTER_VALIDATE_EMAIL)
            && explode('@', filter_var($issuer, FILTER_VALIDATE_EMAIL))[1];
        if (!$domain || !in_array($domain, [
            'securite-agents-enedis.fr',
            'securite-agents-enedis.com',
            'relation-client-enedis.fr',
            'relation-client-enedis.com',
            'smartpush-enedis.fr',
            'smartpush-enedis.com',
        ])) {
            throw new CoreException("invalid issuer: {$issuer}");
        }
    }

    /**
     * checkRecipients check if $recipients is valid
     *
     * @param  string $recipients
     * @return void
     */
    private function checkRecipients(&$recipients, string $mode)
    {
        $type = gettype($recipients);
        if (!$this->useForce && $type !== "array") {
            throw new CoreException("Invalid recipients type : was waiting for an array, got a {$type}");
        }
        if ($type === "string") {
            $recipients = explode(',', str_replace(';', ',', $recipients));
        }
        switch ($mode) {
            case 'email':
                $validEmails = array_filter($recipients, function ($email) {
                    return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
                });
                $deletedCount = count($recipients) - count($validEmails);
                if (!$this->useForce && $deletedCount > 0) {
                    throw new CoreException("There is {$deletedCount} invalid emails in the recipient list. "
                        . "Use the useForce fonction to proceed anyway.");
                }
                $recipients = $validEmails;

                break;
            case 'sms':
                $validFrenchPhoneNumbers = $this->_filterValidFrenchPhoneNumbers($recipients);
                $deletedCount = count($recipients) - count($validFrenchPhoneNumbers);
                if (!$this->useForce && $deletedCount > 0) {
                    throw new CoreException("There is {$deletedCount} invalid phone numbers in the recipient list. "
                        . "Use the useForce fonction to proceed anyway.");
                }
                $recipients = $validFrenchPhoneNumbers;

                break;
            default:
                throw new CoreException("Lost in the universe...");
        }
    }

    private function _cleanFrenchPhoneNumber($phoneNumber)
    {
        $cleaned = preg_replace('/\D/', '', $phoneNumber);
        if (strpos($cleaned, '33') === 0) {
            $cleaned = substr($cleaned, 2);
        }
        if (strpos($cleaned, '0') === 0) {
            $cleaned = substr($cleaned, 1);
        }
        if (strlen($cleaned) === 9 && preg_match('/^[6-7]/', $cleaned[0]) && preg_match('/^\d/', $cleaned)) {
            return '+33' . $cleaned;
        }

        return '';
    }
    private function _filterValidFrenchPhoneNumbers($phoneNumbers)
    {
        $cleanedNumbers = array_map([$this, '_cleanFrenchPhoneNumber'], $phoneNumbers);

        return array_filter($cleanedNumbers);
    }

    public function useForce(bool $value)
    {
        $this->useForce = $value;
    }

    /**
     * sendEmail sends an e-mail
     *
     * @param  string $category the category of message
     * @param  mixed  $recipients the recipients, both 'john@doe.com,mary@doe.com'
     *                            or ['john@doe.com','mary@doe.com'] accepted
     * @param  string $subject subject of the e-mail
     * @param  string $message content of the e-mail
     * @param  string $issuer e-mail adress of sender
     * @param  string $replyTo e-mail adress for the replies
     * @param  string $signature signature of the e-mail
     * @param  string $footer footer of the e-mail
     * @param  array $joinedDocuments array of documents
     * @return void
     */
    public function sendEmail(
        string $category,
        mixed $recipients,
        string $subject,
        string $message,
        string $issuer = 'no-reply@smartpush-enedis.fr',
        string $replyTo = '',
        string $header = '',
        string $signature = '',
        string $footer  = '',
        array $joinedDocuments = []
    ) {
        try {
            $this->checkCategory($category);
            $this->checkIssuer($issuer);
            $this->checkRecipients($recipients, "email");

            $postFields = [
                'category' => $category,
                'recipients' => $recipients,
                'subject' => $subject,
                'message_body' => $message,
                'issuer' => $issuer,
            ];

            '' !== $header
                && $postFields['header'] = $header;
            '' !== $replyTo
                && $postFields['reply_to'] = $replyTo;
            '' !== $signature
                && $postFields['signature'] = $signature;
            '' !== $footer
                && $postFields['footer'] = $footer;
            is_array($joinedDocuments)
                && (count($joinedDocuments) > 0)
                && $postFields['attachments'] = $joinedDocuments;

            return $this->apigile->postQuery('/messages/v2/email', $postFields);
        } catch (CoreException $e) {
            $this->throwException($e);
        }
    }

    private function throwException($e)
    {
        $code = $e->getCode();
        $message = $e->getMessage();

        if ($this->apigile->getRawErrors() === true) {
            throw new CoreException('HTTP ' . $code . ' : ' . $message, $code);
        }
        $frenchMessage = '';
        if (400 === $code) {
            $frenchMessage = 'Erreur dans le format de la requête.';
        }
        if (401 === $code) {
            $frenchMessage = 'Cette API nécessite une authentification de l\'utilisateur.';
        }
        if (403 === $code) {
            $frenchMessage = 'Le token OAuth généré ne vous donne pas droit d\'accéder à cette API.';
        }
        if (406 === $code) {
            $frenchMessage = 'Le format demandé dans le header Accept ne correspond pas à un format géré par APIGILE. '
                . 'Veuillez vous référer à la documentation de l\'API.';
        }
        if (415 === $code) {
            $frenchMessage = 'Les header Accept et/ou Content-Type sont absents ou invalides.';
        }
        if (500 === $code) {
            $frenchMessage = 'Erreur interne à la plateforme d\'API. Veuillez contacter le support.';
        }

        throw new CoreException($frenchMessage . "\n" . $message, $code);
    }

    /**
     * sendSms sends a SMS
     *
     * @param  string $category the category of message
     * @param  mixed  $recipients the recipients, array of numbers or comma separated string
     * @param  string $message content of the SMS
     * @param  string $header header of the SMS
     * @param  string $signature signature of the SMS
     * @return void
     */
    public function sendSms(
        string $category,
        mixed $recipients,
        string $message,
        string $header = "",
        string $signature = ""
    ) {
        try {
            $this->checkCategory($category);
            $this->checkRecipients($recipients, "sms");


            $postFields = [
                'sender_id' => 'ENEDIS',
                'category' => $category,
                'recipients' => $recipients,
                'message_body' => $message
            ];

            '' !== $header && $postFields['header'] = $header;
            '' !== $signature && $postFields['signature'] = $signature;

            return $this->apigile->postQuery('/messages/v2/sms', $postFields);
        } catch (CoreException $e) {
            if ($this->apigile->getRawErrors() === true) {
                throw new CoreException('HTTP ' . $e->getCode() . ' : ' . $e->getMessage(), $e->getCode());
            }

            throw new CoreException($e->getMessage(), $e->getCode());
        }
    }
}
