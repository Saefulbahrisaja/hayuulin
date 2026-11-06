<?php

namespace App\Services;

class NaiveBayesSentimentClassifier
{
    private const CORPUS = [
        'positif' => [
            'tempatnya sangat indah dan menyenangkan',
            'pemandangan bagus pelayanan ramah',
            'wisata nyaman bersih dan menarik',
            'pengalaman luar biasa ingin kembali',
            'akses mudah fasilitas lengkap',
            'pantainya bersih pemandangannya keren',
            'air terjun bagus dan suasana sejuk',
            'petugas membantu dan informatif',
        ],
        'negatif' => [
            'tempat kotor dan tidak terawat',
            'pelayanan buruk sangat mengecewakan',
            'akses sulit jalan rusak parah',
            'harga mahal fasilitas tidak memadai',
            'antrean panjang dan pengelolaan buruk',
            'sampah berserakan suasana tidak nyaman',
            'tiket terlalu mahal dan tidak sepadan',
            'petugas tidak ramah pengalaman buruk',
        ],
        'netral' => [
            'lokasi berada di wilayah sukabumi',
            'tempat buka pada pagi hari',
            'perjalanan membutuhkan waktu sekitar satu jam',
            'tersedia area parkir untuk pengunjung',
            'wisata ini dapat dikunjungi bersama keluarga',
            'tiket tersedia di loket masuk',
            'terdapat beberapa fasilitas di lokasi',
            'pengunjung dapat datang saat akhir pekan',
        ],
    ];

    public function classify(string $text): array
    {
        $tokens = $this->tokens($text);
        $vocabulary = [];
        $totals = [];
        foreach (self::CORPUS as $sentiment => $documents) {
            $totals[$sentiment] = 0;
            foreach ($documents as $document) {
                $documentTokens = $this->tokens($document);
                $totals[$sentiment] += count($documentTokens);
                $vocabulary = array_merge($vocabulary, $documentTokens);
            }
        }
        $vocabulary = array_unique($vocabulary);
        $scores = [];
        $documentCount = array_sum(array_map('count', self::CORPUS));
        foreach (self::CORPUS as $sentiment => $documents) {
            $score = log(count($documents) / $documentCount);
            foreach ($tokens as $token) {
                $matches = 0;
                foreach ($documents as $document) {
                    $matches += count(array_filter($this->tokens($document), fn ($word) => $word === $token));
                }
                $score += log(($matches + 1) / ($totals[$sentiment] + count($vocabulary)));
            }
            $scores[$sentiment] = $score;
        }

        $maximum = max($scores);
        $probabilities = array_map(fn (float $score) => exp($score - $maximum), $scores);
        $total = array_sum($probabilities);
        foreach ($probabilities as $sentiment => $probability) {
            $probabilities[$sentiment] = $probability / $total;
        }
        arsort($probabilities);
        $sentiment = array_key_first($probabilities);

        return [
            'sentiment' => $sentiment,
            'confidence' => round($probabilities[$sentiment], 4),
            'probabilities' => $probabilities,
            'model' => 'Multinomial Naive Bayes · korpus awal bahasa Indonesia',
        ];
    }

    private function tokens(string $text): array
    {
        preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($text), $matches);

        return $matches[0];
    }
}
