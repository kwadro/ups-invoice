<?php

namespace Application\Controller;

use CURLFile;
use FilesystemIterator;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class HelloController extends AbstractActionController
{
    public const API_ENDPOINT_URL = 'https://printsafari.loc/pdf/load';
    // use only for local testing
    public function worldAction(): ViewModel
    {
        echo '<h1>Access Denied</h1>';
        exit;

        $files = $this->loadFiles('/CA');
        echo 'API_ENDPOINT_URL: ' . self::API_ENDPOINT_URL . '<br/>';
        echo 'count files: ' . count($files) . '<br/>';
        $html = '';
        $errors2 = 0;
        $errors = 0;
        $files= [
            //'/home/igor/home/pdftest/comercialinvoices/US025620_1_1_.PDF',
            //'/home/igor/home/pdftest/comercialinvoices/US025619_1_1_ComInv_p1.PDF',
            '/home/igor/home/pdftest/comercialinvoices/US025620_1_1_ComInv_p1 (4).PDF',
        ];
        foreach ($files as $file) {

            echo 'file: ' . $file . '<br/>';

            $response = $this->getCurlRequest($file);

            if ($response['status'] == 'success') {
                if ($response['errors2'] > 0) {
                    $errors2++;
                }
                if ($response['errors'] > 0) {
                    $errors++;
                    foreach ($response as $key => $value) {
                        if (is_array($value)) {
                            foreach ($value as $k => $v) {
                                $html .= $k . ': ' . $v . '<br>';
                            }
                        } else {
                            $html .= $key . ': ' . $value . '<br>';
                        }
                    }
                }
                if ($response['errors2'] >= 0 && $response['errors'] == 0) {
                    $parseResponse='';
                    foreach ($response['parseData'] as $key => $value) {
                        $parseResponse .= $key . ': ' . $value . '<br>';
                    }
                    echo $parseResponse . '<br/>';
                    echo $response['str'] . '<br/>';
                }
            } else {
                $html .= $response['message'];
            }

            echo 'errors2: ' . $errors2 . '<br/>';
            echo 'errors: ' . $errors . '<br/>';
            echo $html . '<br/>';

        }
        exit;
    }

    public function getCurlRequest($filePath): array
    {
        $ch = curl_init();

        $postFields = [
            'file' => new CURLFile($filePath),
            'other_field' => 'value' // optional other fields
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => self::API_ENDPOINT_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,

        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $res = ['message' => 'cURL error: ' . curl_error($ch), 'status' => 'error'];
        } else {

            $res = json_decode($response, true);
        }
        curl_close($ch);
        return $res;
    }

    public function loadFiles($pref): array
    {
        $files = [];
        $path = '/home/igor/home/pdftest/comercialinvoices';
        $rdi = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $number = 0;
        foreach (new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::SELF_FIRST) as $file => $info) {
            if (!str_contains($file, $pref)) {
                continue;
            }
            $files[] = $file;
            if ($number > 3000000000) {
                break;
            }
            $number++;
        }
        return $files;
    }
}
