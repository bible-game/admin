<?php

namespace App\Controllers;

class Passage extends BaseController
{
    private string $passageServiceBaseUrl;
    private array $bibleStructure;

    public function __construct()
    {
        $this->passageServiceBaseUrl = env('SVC_PASSAGE', 'http://localhost:8081');
        $this->bibleStructure = $this->loadBibleStructure();
    }

    private function loadBibleStructure(): array
    {
        $jsonPath = ROOTPATH . 'bible.json';
        log_message('debug', 'Attempting to load bible.json from: ' . $jsonPath);
        if (!file_exists($jsonPath)) {
            log_message('error', 'bible.json not found at ' . $jsonPath);
            return [];
        }
        $jsonContent = file_get_contents($jsonPath);
        if ($jsonContent === false) {
            log_message('error', 'Failed to read content from bible.json at ' . $jsonPath);
            return [];
        }
        $decodedContent = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'JSON decoding error for bible.json: ' . json_last_error_msg());
            return [];
        }
        log_message('debug', 'Successfully loaded and decoded bible.json.');
        return $decodedContent ?: [];
    }

    public function index(): string
    {
        $earliestDate = $this->getEarliestDate();
        return view('passage', ['earliestDate' => $earliestDate]);
    }

    public function getHeatmapData()
    {
        $data = $this->generateHeatmapData();
        return $this->response->setJSON($data);
    }

    private function getEarliestDate(): ?string
    {
        $historyUrl = $this->passageServiceBaseUrl . '/daily/history';
        $historyResponse = $this->fetchUrl($historyUrl);
        $dates = json_decode($historyResponse, true);

        if (is_array($dates) && !empty($dates)) {
            usort($dates, static fn($a, $b) => strtotime($a) <=> strtotime($b));
            return $dates[0];
        }

        return null;
    }

    private function generateHeatmapData(): array
    {
        $chapterCounts = [];
        $bookNameToKeyMap = [];

        foreach ($this->bibleStructure['testaments'] as $testament) {
            foreach ($testament['divisions'] as $division) {
                foreach ($division['books'] as $book) {
                    $bookNameToKeyMap[$book['name']] = $book['key'];
                    for ($i = 1; $i <= $book['chapters']; $i++) {
                        $chapterCounts[$book['key']][$i] = 0;
                    }
                }
            }
        }

        // 1. Fetch history of dates
        $historyUrl = $this->passageServiceBaseUrl . '/daily/history';
        $historyResponse = $this->fetchUrl($historyUrl);
        log_message('debug', 'History Response: ' . ($historyResponse ?? 'null'));
        $dates = json_decode($historyResponse, true);
        log_message('debug', 'Decoded Dates: ' . json_encode($dates));

        if (is_array($dates)) {
            foreach ($dates as $date) {
                // 2. Fetch daily passage for each date
                $dailyPassageUrl = $this->passageServiceBaseUrl . '/daily/' . $date;
                $dailyPassageResponse = $this->fetchUrl($dailyPassageUrl);
                log_message('debug', 'Daily Passage Response for ' . $date . ': ' . ($dailyPassageResponse ?? 'null'));
                $passageData = json_decode($dailyPassageResponse, true);
                log_message('debug', 'Decoded Passage Data for ' . $date . ': ' . json_encode($passageData));

                // Assuming passageData contains 'book' and 'chapter'
                if (isset($passageData['book']) && isset($passageData['chapter'])) {
                    $bookName = $passageData['book'];
                    $chapter = (int)$passageData['chapter'];

                    if (isset($bookNameToKeyMap[$bookName])) {
                        $bookKey = $bookNameToKeyMap[$bookName];
                        if (isset($chapterCounts[$bookKey][$chapter])) {
                            $chapterCounts[$bookKey][$chapter]++;
                            log_message('debug', 'Incremented count for ' . $bookKey . ' chapter ' . $chapter . ': ' . $chapterCounts[$bookKey][$chapter]);
                        }
                    }
                }
            }
        }

        $heatmapData = [];
        $bookIndex = 0;
        foreach ($this->bibleStructure['testaments'] as $testament) {
            foreach ($testament['divisions'] as $division) {
                foreach ($division['books'] as $book) {
                    for ($i = 1; $i <= $book['chapters']; $i++) {
                        $heatmapData[] = [
                            'testament' => $testament['name'],
                            'division' => $division['name'],
                            'bookName' => $book['name'],
                            'bookKey' => $book['key'],
                            'chapter' => $i,
                            'value' => $chapterCounts[$book['key']][$i] ?? 0
                        ];
                    }
                    $bookIndex++;
                }
            }
        }

        return $heatmapData;
    }

    private function fetchUrl(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            log_message('error', 'cURL Error: ' . curl_error($ch));
            return null;
        }
        curl_close($ch);
        return $response;
    }
}
