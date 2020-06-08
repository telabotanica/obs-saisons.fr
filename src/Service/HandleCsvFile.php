<?php

namespace App\Service;

class HandleCsvFile
{
    public function parseCsv(string $file): array
    {
        $data = [];
        if (file_exists($file) && is_readable($file)) {
            $header = null;
            if (false !== ($handle = fopen($file, 'r'))) {
                while (false !== ($row = fgetcsv($handle, 1000, ','))) {
                    if (!$header) {
                        $header = $row;
                    } else {
                        $data[] = array_combine($header, $row);
                    }
                }
                fclose($handle);
            }
        }

        return $data;
    }
}
