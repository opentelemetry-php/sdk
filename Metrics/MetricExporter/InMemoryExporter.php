<?php

declare(strict_types=1);

namespace OpenTelemetry\SDK\Metrics\MetricExporter;

use function array_push;
use OpenTelemetry\SDK\Metrics\AggregationTemporalitySelectorInterface;
use OpenTelemetry\SDK\Metrics\Data\Metric;
use OpenTelemetry\SDK\Metrics\Data\Temporality;
use OpenTelemetry\SDK\Metrics\MetricExporterInterface;
use OpenTelemetry\SDK\Metrics\MetricMetadataInterface;

/**
 * @see https://github.com/open-telemetry/opentelemetry-specification/blob/main/specification/metrics/sdk_exporters/in-memory.md
 */
final class InMemoryExporter implements MetricExporterInterface, AggregationTemporalitySelectorInterface
{
    /**
     * @var list<Metric>
     */
    private array $metrics = [];

    private bool $closed = false;

    public function __construct(private readonly string|Temporality|null $temporality = null)
    {
    }

    public function temporality(MetricMetadataInterface $metric): string|Temporality|null
    {
        return $this->temporality ?? $metric->temporality();
    }

    /**
     * @return list<Metric>
     */
    public function collect(bool $reset = false): array
    {
        $metrics = $this->metrics;
        if ($reset) {
            $this->metrics = [];
        }

        return $metrics;
    }

    public function export(iterable $batch): bool
    {
        if ($this->closed) {
            return false;
        }

        /** @psalm-suppress InvalidPropertyAssignmentValue */
        array_push($this->metrics, ...$batch);

        return true;
    }

    public function shutdown(): bool
    {
        if ($this->closed) {
            return false;
        }

        $this->closed = true;

        return true;
    }
}
