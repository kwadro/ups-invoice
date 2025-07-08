<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class GuideController extends AbstractActionController
{


    public function indexAction(): ViewModel
    {
        $exampleCode = $this->getExampleCode();
        $description = '$filePath - is absolute path to commercial invoice as pdf file';
        return new ViewModel(['exampleCode' => $exampleCode, 'description' => $description]);
    }

    public function getExampleCode()
    {
        return '
public function getCurlRequest($filePath): array
    $ch = curl_init();

    $postFields = [
        "file" => new CURLFile($filePath)
    ];

    curl_setopt_array($ch, [
        CURLOPT_URL => "https://www.kwadro.com.ua/pdf/load",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,

    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $res = ["message" => "cURL error: " . curl_error($ch), "status" => "error"];
    } else {
        $res = json_decode($response, true);
    }
    curl_close($ch);
    return $res;
}';
    }
}
