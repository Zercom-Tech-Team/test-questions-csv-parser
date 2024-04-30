<?php

namespace HandleCsv;

class HandleCsv
{
    public $csvFile;
    public function __construct($csvFile)
    {
        $this->csvFile = $csvFile;
    }

    public function readCSVFile($columnName)
    {
        $groupedData = [];

        $file = fopen($this->csvFile, "r");
        $headers = fgetcsv($file);
        $index = $this->findColumnIndex($headers, $columnName);

        while (($line = fgetcsv($file)) !== false) {
            $key = $line[$index];
            $groupedData[$key][] = $line;
        }

        fclose($file);

        foreach ($groupedData as $group) {
            $this->processGroupedData($group);
        }
    }

    public function processGroupedData($group)
    {
        $fileName = $this->generateFileName($group[0][4]);
        try {
            foreach ($group as $item) {
                !empty($item[6]) &&
                    ($item[6] = $this->processSerializedOptions($item[6]));
                !empty($item[7]) && ($item[7] = unserialize($item[7]));

                if ($item[2] === "true_false") {
                    $item[6] = $this->setTrueFalseOptions();
                }

                $this->formatInHtml($item, $fileName);
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
    }

    public function formatInHtml($testItem, $fileName)
    {
        $formattedText =
            "::" .
            $this->sanitizeInput($testItem[1]) .
            "::[html]<p>" .
            $this->sanitizeInput($testItem[1]) .
            "?<br></p>{\n";

        foreach ($testItem[6] as $key => $value) {
            $formattedText .= "\t";
            if ($key === (int) $testItem[7]) {
                $formattedText .=
                    "=<p>" . $this->sanitizeInput($value) . "</p>\n";
            } else {
                $formattedText .=
                    "~<p>" . $this->sanitizeInput($value) . "</p>\n";
            }
        }

        $formattedText .= "}\n\n";

        file_put_contents($fileName, $formattedText, FILE_APPEND);
    }

    private function sanitizeInput($input)
    {
        $input = trim($input);
        // $input = preg_replace('/[^a-zA-Z0-9\s.,?!\'"-]/', "", $input);
        return $input;
    }

    private function generateFileName($group)
    {
        $folderPath = "questions";
        $perm = 0775;

        if (!dir($folderPath) && !mkdir($folderPath, $perm, true)) {
            error_log("Failed to create folder");
        }

        if (!chmod($folderPath, $perm)) {
            error_log("Failed to set permissions");
        }

        return $folderPath . "/" . $group . "-" . uniqid() . time() . ".txt";
    }

    private function setTrueFalseOptions()
    {
        return [
            1 => "true",
            0 => "false",
        ];
    }

    private function processSerializedOptions($data)
    {
        $dataArr = unserialize($data);
        if (is_array($dataArr)) {
            for ($i = 0; $i < count($dataArr); $i++) {
                $new = explode(" ", $dataArr[$i]);
                if (in_array($new[0], ["A)", "a)", "B)", "b)"])) {
                    unset($new[0]);
                }
                $dataArr[$i] = implode(" ", $new);
            }
        }
        return $dataArr;
    }

    private function findColumnIndex($headers, $columnName)
    {
        return array_search($columnName, $headers);
    }
}
