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
    const TEST_MODE = false;

    public function loadAction()
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        if (!self::TEST_MODE && !in_array($ip, $this->getAvailableIps())) {
            $response = ['status' => 'error', 'message' => 'IP Not Available'];
            echo json_encode($response);
            exit;
        }

        if ($this->getRequest()->isPost()) {
            $pdfFileData = $this->loadPdfFile();
            $mineType = $pdfFileData['mineType'];
            if ($pdfFileData['status'] === 'error') {
                echo json_encode(['errormessage' => $pdfFileData['message']]);
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
                    $map = [
                        'orderDate' => [
                            [332.2026, 654.5980999999999]
                        ],
                        'orderId' => [[355.3159, 645.6481]],
                        'total' => [
                            [550.4966, 155.73239999999998],
                            [550.4966, 168.63239999999996],
                            [542.9908, 168.63239999999996],
                            [543.6588, 168.63239999999996],
                            [547.9962, 168.63239999999996],
                            [551.1646, 168.63239999999996],
                            [555.502, 168.63239999999996],
                            [555.502, 155.73239999999998],
                            [542.9908, 155.73239999999998],
                            [551.8326, 155.73239999999998],
                            [556.1700, 168.63239999999996],
                            [548.6641, 168.63239999999996],
                            [544.3267, 168.63239999999996],
                        ],
                        'freight' => [
                            [550.4966, 115.23239999999998],
                            [560.5074, 128.13239999999996],
                            [555.502, 115.23239999999998],
                            [560.5074, 114.63239999999996],
                            [542.9908, 115.23239999999998],
                            [542.9908, 128.73239999999998],
                            [555.502, 128.13239999999996],
                            [550.4966, 128.13239999999996]
                        ],
                    ];
                    $find = [];
                    $result = [];
                    $showKeys = [57, 60, 61];
                    foreach ($data as $key => $item) {
                        if (self::TEST_MODE && in_array($key, $showKeys)) {
                            $str .= 'l0: ' . $key . ': </br/>';
                        }
                        foreach ($item as $key2 => $value2) {
                            if (self::TEST_MODE && in_array($key, $showKeys)) {
                                $str .= 'l1: ' . $key2 . '-' . json_encode($value2) . '</br/>';
                            }

                            if ($key2 === 0) {
                                foreach ($map as $field => $mapValues) {
                                    foreach ($mapValues as $mapValue) {
                                        if ($value2[4] === $mapValue[0] && $value2[5] === $mapValue[1]) {
                                            $find[] = $field;
                                            break;
                                        }
                                    }
                                }
                            }
                            if (self::TEST_MODE && in_array($key, $showKeys)) {
                                $str .= 'find field: ' . json_encode($find) . '</br/>';
                            }
                            if ($key2 === 1 && !empty($find)) {
                                if (!isset($result[$find[0]])) {
                                    $result[$find[0]] = $value2;
                                } else {
                                    if ($value2 > $result[$find[0]]) {
                                        $result[$find[0]] = $value2;
                                    }
                                }
                                $find = [];
                            }
                            if (self::TEST_MODE && in_array($key, $showKeys)) {
                                $str .= 'result: ' . json_encode($result) . '</br/>';
                            }
                        }
                    }

                    $freight = $result['freight'] ?? '';

                    $total = $result['total'] ?? '';
                    $parseData = [
                        'orderDate' => $result['orderDate'],
                        'orderId' => $result['orderId'],
                        'total' => $total,
                        'freight' => $freight,
                    ];
                    if (empty($total) || empty($freight) || $freight === '0.00') {
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
                        if (empty($total) || empty($freight)) {
                            $errors++;
                        } else {
                            $parseData = [
                                'orderDate' => $result['orderDate'],
                                'orderId' => $result['orderId'],
                                'total' => $total,
                                'freight' => $freight,
                            ];
                        }
                    }
                } else {
                    echo 'error loading pages';
                }
                $response = [
                    'parseData' => $parseData,
                    'message' => 'Parse done',
                    'status' => 'success',
                    'errors2' => $errors2,
                    'errors' => $errors,
                    'mapTotal' => $mapTotal,
                    'mapFright' => $mapFright,
                    'mineType' => $mineType,
                    'str' => $str
                ];
            } else {
                $response = ['message' => 'Can\'t load file', 'status' => 'error'];
            }
            echo json_encode($response);
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
                if ($fileSize > self::MAX_FILE_SIZE) {
                    return ['status' => 'error', 'message' => 'Max File Size is more than 2Mb.'];
                }
                if ($mineType !== self::ALLOW_MINE_TYPE) {
                    return ['status' => 'error', 'message' => sprintf('File type %s is disallow', $mineType)];
                }
                $uploadFileDir = __DIR__ . '/uploads/';
                $destPath = $uploadFileDir . basename($fileName);

                // Make sure upload directory exists
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $result = ['status' => 'success', 'filePath' => $destPath, 'mineType' => $mineType];
                } else {
                    $result = ['status' => 'error', 'message' => 'Error moving uploaded file.'];
                    echo "Error moving uploaded file.";
                }
            } else {
                $result = ['status' => 'error', 'message' => 'No file uploaded or upload error.'];
            }
        }
        return $result;
    }

    public function getAvailableIps()
    {
        return ['18.192.89.123', '3.66.225.226'];
    }
}
