<?php
/*
 *  @author MST1122
 *  @email: sikandartariq25@gmail.com
 */

namespace DoubleBreak\Spapi\Helper;


use DoubleBreak\Spapi\ASECryptoStream;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class Feeder
{
    /**
     * @param $payload : Response from createFeedDocument Function. e.g.: response['payload']
     * @param $contentType : Content type used during createFeedDocument function call.
     * @param $feedFile : The path or content of the file that contains the data to be uploaded.
     * @return string
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function uploadFeedDocument($payload, $contentType, $feedFile)
    {
        $encryptionDetails = $payload['encryptionDetails'];
        $feedUploadUrl = $payload['url'];

        $key = $encryptionDetails['key'];
        $initializationVector = $encryptionDetails['initializationVector'];

        // base64 decode before using in encryption
        $initializationVector = base64_decode($initializationVector, true);
        $key = base64_decode($key, true);

        // get file to upload
        $fileResourceType = gettype($feedFile);

        // resource or string ? make it to a string
        if ($fileResourceType == 'resource') {
            $file = stream_get_contents($feedFile);
        } elseif (file_exists($feedFile)) {
            $file = file_get_contents($feedFile);
        } else {
            $file = $feedFile;
        }

        // utf8 !
        $file = utf8_encode($file);

        // encrypt string and get value as base64 encoded string
        $encryptedFile = ASECryptoStream::encrypt($file, $key, $initializationVector);

        // my http client
        $client = new Client(['exceptions' => false]);

        $request = new Request(
        // PUT!
            'PUT',
            // predefined url
            $feedUploadUrl,
            // content type equal to content type from response createFeedDocument-operation
            array('Content-Type' => $contentType),
            // resource File
            $encryptedFile
        );

        $response = $client->send($request);
        $HTTPStatusCode = $response->getStatusCode();

        if ($HTTPStatusCode == 200) {
            return 'Done';
        } else {
            return $response->getBody()->getContents();
        }
    }

    /**
     * @param $payload : Response from getFeedDocument Function. e.g.: response['payload']
     * @return string : Feed Processing Report.
     */
    public function downloadFeedProcessingReport($payload)
    {
        $encryptionDetails = $payload['encryptionDetails'];
        $feedDownloadUrl = $payload['url'];

        $key = $encryptionDetails['key'];
        $initializationVector = $encryptionDetails['initializationVector'];

        // base64 decode before using in encryption
        $initializationVector = base64_decode($initializationVector, true);
        $key = base64_decode($key, true);

        $decryptedFile = ASECryptoStream::decrypt(file_get_contents($feedDownloadUrl), $key, $initializationVector);
        if(isset($payload['compressionAlgorithm']) && $payload['compressionAlgorithm']=='GZIP') {
            $decryptedFile = gzdecode($decryptedFile);
        }
        return $decryptedFile;
    }
}
