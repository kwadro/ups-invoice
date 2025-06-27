<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;

class PdfController extends AbstractActionController
{
    const MAX_FILE_SIZE = 2097152;
    const ALLOW_MINE_TYPE = 'application/pdf';

    public function loadAction()
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        if(!in_array($ip, $this->getAvailableIps())){
            $response =['status'=>'error','message'=>'IP Not Available'];
            echo   json_encode($response);
            exit;
        }

        if($this->getRequest()->isPost()) {
            $pdfFileData = $this->loadPdfFile();
            $mineType = $pdfFileData['mineType'];
            if($pdfFileData['status'] === 'error') {
                echo   json_encode(['errormessage'=>$pdfFileData['message']]);
                exit;
            }
            $pdfFilePath = $pdfFileData['filePath'];
            $config = new Config();
            $config->setDataTmFontInfoHasToBeIncluded(true);
            $parser = new Parser([], $config);
            $pdf = $parser->parseFile($pdfFilePath);
            $pr = $pdf->getDetails()['Producer'];
            if ($pr) {
                $errors = 0;
                $errors2 = 0;
                $mapTotal = [];
                $mapFright = [];
                $pages = $pdf->getPages();
                $parseData = [];
                if ($pages) {
                    $firstPage = $pages[0];
                    $data = $firstPage->getDataTm();
                    $str = '';
                    $orderId = 0;

                    $map = [
                        'orderDate' => [332.2026, 654.5980999999999],
                        'orderDate1' => [332.2026,654.5980999999999],
                        'orderId' => [355.3159, 645.6481],
                        'total' => [550.4966, 155.73239999999998],
                        'total1' => [550.4966, 168.63239999999996],
                        'total2' => [542.9908, 168.63239999999996],
                        'total3' => [543.6588, 168.63239999999996],
                        'total4' => [547.9962, 168.63239999999996],
                        'total5' => [551.1646, 168.63239999999996],
                        'total6' => [555.502, 168.63239999999996],
                        'total7' => [555.502, 155.73239999999998],
                        'total8' => [542.9908, 155.73239999999998],
                        'total9' => [551.8326, 155.73239999999998],

                        'total10' => [556.1700, 168.63239999999996],
                        'total11' => [548.6641, 168.63239999999996],
                        'total12' => [544.3267, 168.63239999999996],

                        'freight' => [550.4966, 115.23239999999998],
                        'freight1' => [560.5074,128.13239999999996],
                        'freight2' => [555.502, 115.23239999999998],
                        'freight3' => [560.5074,114.63239999999996],
                        'freight4' => [542.9908,115.23239999999998],
                    ];
                    $find = [];
                    $result = [];
                    foreach ($data as $key => $item) {
                        $str .= 'l0: ' . $key . ': </br/>';

                        foreach ($item as $key2 => $value2) {
                            $str .= 'l1: ' . $key2 . '-' . json_encode($value2) . '</br/>';
                            if ($key2 === 0) {
                                foreach ($map as $field => $mapValue) {
                                    if ($value2[4] === $mapValue[0] && $value2[5] === $mapValue[1]) {
                                        $find[] = $field;
                                    }
                                }
                            }
                            if ($key2 === 1 && !empty($find)) {
                                $result[$find[0]] = $value2;
                                $find = [];
                            }
                        }
                    }

                    $freight = $result['freight'] ?? $result['freight1'] ?? $result['freight2']
                        ?? $result['freight3'] ?? $result['freight4'] ?? '';

                    $total = $result['total'] ?? $result['total1'] ?? $result['total2']
                        ?? $result['total3'] ?? $result['total4'] ?? $result['total5']
                        ?? $result['total6'] ?? $result['total7'] ?? $result['total8']
                        ?? $result['total9'] ?? $result['total10'] ?? $result['total11']
                        ?? $result['total12'] ?? '';
                    $parseData = [
                        'orderDate' => $result['orderDate'],
                        'orderId' => $result['orderId'],
                        'total' => $total,
                        'freight' => $freight,
                    ];
                    if (empty($total)||empty($freight)) {
                        $errors2++;
                        $total = $data[53][1];
                        $freight = $data[57][1];
                        $mapAdded = false;
                        if (str_contains($total, 'SALE Statistical Value')) {
                            $total = $data[57][1];
                            $mapTotal[] = [0 => $data[57][0][4], 1 => $data[57][0][5]];
                            $freight = $data[60][1];
                            $mapFright[] = [0 => $data[60][0][4], 1 => $data[60][0][5]];
                            $mapAdded = true;
                        }
                        if (str_contains($total, 'The statistical value')) {
                            $total = $data[58][1];
                            $mapTotal[] = [0 => $data[58][0][4], 1 => $data[58][0][5]];
                            $freight = $data[61][1];
                            $mapFright[] = [0 => $data[61][0][4], 1 => $data[61][0][5]];
                            $mapAdded = true;
                        }
                        if (!$mapAdded) {
                            $mapTotal[] = [0 => $data[53][0][4], 1 => $data[53][0][5]];
                            $mapFright[] = [0 => $data[57][0][4], 1 => $data[57][0][5]];
                        }
                        if (empty($total)||empty($freight)) {
                            $errors++;
                        }else{
                            $parseData = [
                                'orderDate' => $orderId,
                                'orderId' => $result['orderId'],
                                'total' => $total,
                                'freight' => $freight,
                            ];
                        }
                    }

                } else {
                    echo 'error loading pages';
                }
                $response =  [
                    'parseData' => $parseData,
                    'message' => 'Parse done',
                    'status' => 'success',
                    'errors2' => $errors2,
                    'errors' => $errors,
                    'mapTotal' => $mapTotal,
                    'mapFright' => $mapFright,
                    'mineType' => $mineType,
                    //'str' => $str
                ];
            } else {
                $response = ['message' => 'Can\'t load file', 'status' => 'error'];
            }
            echo   json_encode($response);
            exit;
        }
        // restrict get request
        echo '<h1>Access Denied</h1>';
        exit;
    }

    private function loadPdfFile(): array
    {
        $result = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['file']['tmp_name'];
                $fileName = $_FILES['file']['name'];
                $fileSize = $_FILES['file']['size'];
                $mineType = mime_content_type($_FILES['file']['tmp_name']);
                $fileType = strtolower($_FILES['file']['type']);
                if($fileSize > self::MAX_FILE_SIZE ) {
                    return ['status'=>'error','message' => 'Max File Size is more than 2Mb.'];
                }
                if($mineType !== self::ALLOW_MINE_TYPE) {
                    return ['status'=>'error','message' => sprintf('File type %s is disallow',$mineType)];
                }
                $uploadFileDir = __DIR__ . '/uploads/';
                $destPath = $uploadFileDir . basename($fileName);

                // Make sure upload directory exists
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $result = ['status'=>'success','filePath' => $destPath,'mineType' => $mineType];
                } else {
                    $result = ['status'=>'error','message' => 'Error moving uploaded file.'];
                    echo "Error moving uploaded file.";
                }
            } else {
                $result = ['status'=>'error','message' => 'No file uploaded or upload error.'];

            }
        }
        return $result;
    }
    public function getAvailableIps()
    {
        return ['127.0.0.1','18.192.89.123','3.66.225.226'];
    }
}
