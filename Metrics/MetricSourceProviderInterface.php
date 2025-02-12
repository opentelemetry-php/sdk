<?php

declare(strict_types=1);

namespace OpenTelemetry\SDK\Metrics;

use OpenTelemetry\SDK\Metrics\Data\Temporality;

interface MetricSourceProviderInterface
{
    public function create(Temporality $temporality): MetricSourceInterface;
}
