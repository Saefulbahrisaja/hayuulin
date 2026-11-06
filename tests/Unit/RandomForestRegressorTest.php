<?php

namespace Tests\Unit;

use App\Services\RandomForestRegressor;
use PHPUnit\Framework\TestCase;

class RandomForestRegressorTest extends TestCase
{
    public function test_regression_uses_training_examples_and_requires_minimum_history(): void
    {
        $model = new RandomForestRegressor;
        $samples = [];
        $targets = [];
        for ($index = 1; $index <= 24; $index++) {
            $samples[] = [$index, $index % 2];
            $targets[] = $index * 1000;
        }

        $prediction = $model->predict($samples, $targets, [12, 0]);

        $this->assertNotNull($prediction);
        $this->assertGreaterThan(0, $prediction);
        $this->assertLessThanOrEqual(24000, $prediction);
        $this->assertNull($model->predict(array_slice($samples, 0, 11), array_slice($targets, 0, 11), [12, 0]));
    }
}
