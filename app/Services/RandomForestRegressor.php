<?php

namespace App\Services;

class RandomForestRegressor
{
    private const TREE_COUNT = 19;

    private const MAX_DEPTH = 5;

    private array $trainedForests = [];

    public function predict(array $samples, array $targets, array $input): ?float
    {
        if (count($samples) < 12 || count($samples) !== count($targets) || ! $input) {
            return null;
        }

        $cacheKey = hash('sha256', serialize([$samples, $targets]));
        if (isset($this->trainedForests[$cacheKey])) {
            $forest = $this->trainedForests[$cacheKey];
            $predictions = array_map(fn (array $tree) => $this->walk($tree, array_values($input)), $forest);

            return array_sum($predictions) / count($predictions);
        }

        $state = 20261106;
        $random = function (int $maximum) use (&$state): int {
            $state = (int) (($state * 48271) % 2147483647);

            return $state % max(1, $maximum);
        };
        $forest = [];
        for ($tree = 0; $tree < self::TREE_COUNT; $tree++) {
            $bootstrap = [];
            for ($row = 0; $row < count($samples); $row++) {
                $index = $random(count($samples));
                $bootstrap[] = ['x' => array_values($samples[$index]), 'y' => (float) $targets[$index]];
            }
            $forest[] = $this->grow($bootstrap, array_values($input), 0, $random);
        }
        $this->trainedForests[$cacheKey] = $forest;

        $predictions = array_map(fn (array $tree) => $this->walk($tree, array_values($input)), $forest);

        return array_sum($predictions) / count($predictions);
    }

    private function grow(array $rows, array $input, int $depth, callable $random): array
    {
        $mean = array_sum(array_column($rows, 'y')) / count($rows);
        if ($depth >= self::MAX_DEPTH || count($rows) < 6) {
            return ['leaf' => true, 'value' => $mean];
        }

        $featureCount = count($input);
        $featureIndexes = range(0, $featureCount - 1);
        $selectedFeatures = [];
        $featurePool = $featureIndexes;
        $featureSamples = min($featureCount, max(1, (int) ceil(sqrt($featureCount))));
        for ($i = 0; $i < $featureSamples; $i++) {
            $selectedIndex = $random(count($featurePool));
            $selectedFeatures[] = $featurePool[$selectedIndex];
            array_splice($featurePool, $selectedIndex, 1);
        }
        $featureIndexes = $selectedFeatures;
        $best = null;
        $parentError = $this->squaredError(array_column($rows, 'y'));

        foreach ($featureIndexes as $feature) {
            $values = array_values(array_unique(array_map(
                fn (array $row) => (float) $row['x'][$feature],
                $rows
            )));
            sort($values);
            if (count($values) < 2) {
                continue;
            }

            $candidateIndexes = range(0, count($values) - 2);
            if (count($candidateIndexes) > 12) {
                $candidateIndexes = array_values(array_unique(array_map(
                    fn (int $i) => (int) round($i * (count($values) - 2) / 11),
                    range(0, 11)
                )));
            }
            foreach ($candidateIndexes as $index) {
                $threshold = ($values[$index] + $values[$index + 1]) / 2;
                $left = array_values(array_filter($rows, fn (array $row) => $row['x'][$feature] <= $threshold));
                $right = array_values(array_filter($rows, fn (array $row) => $row['x'][$feature] > $threshold));
                if (count($left) < 3 || count($right) < 3) {
                    continue;
                }

                $error = $this->squaredError(array_column($left, 'y'))
                    + $this->squaredError(array_column($right, 'y'));
                if ($best === null || $error < $best['error']) {
                    $best = compact('feature', 'threshold', 'left', 'right', 'error');
                }
            }
        }

        if ($best === null || $best['error'] >= $parentError) {
            return ['leaf' => true, 'value' => $mean];
        }

        return [
            'leaf' => false,
            'feature' => $best['feature'],
            'threshold' => $best['threshold'],
            'left' => $this->grow($best['left'], $input, $depth + 1, $random),
            'right' => $this->grow($best['right'], $input, $depth + 1, $random),
        ];
    }

    private function walk(array $node, array $input): float
    {
        if ($node['leaf']) {
            return $node['value'];
        }

        return $this->walk(
            $input[$node['feature']] <= $node['threshold'] ? $node['left'] : $node['right'],
            $input
        );
    }

    private function squaredError(array $values): float
    {
        if (! $values) {
            return 0.0;
        }
        $mean = array_sum($values) / count($values);

        return array_sum(array_map(fn ($value) => ((float) $value - $mean) ** 2, $values));
    }
}
