<?php

declare(strict_types=1);

namespace OpenTelemetry\SDK\Logs;

use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Instrumentation\InstrumentationScopeFactory;
use OpenTelemetry\SDK\Logs\Processor\MultiLogRecordProcessor;
use OpenTelemetry\SDK\Logs\Processor\NoopLogRecordProcessor;
use OpenTelemetry\SDK\Resource\ResourceInfo;

class LoggerProviderBuilder
{
    /** @var array<LogRecordProcessorInterface> */
    private array $processors = [];
    private ?ResourceInfo $resource = null;

    public function addLogRecordProcessor(LogRecordProcessorInterface $processor): self
    {
        $this->processors[] = $processor;

        return $this;
    }

    public function setResource(ResourceInfo $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function build(): LoggerProviderInterface
    {
        return new LoggerProvider(
            $this->buildProcessor(),
            new InstrumentationScopeFactory(Attributes::factory()),
            $this->resource
        );
    }

    private function buildProcessor(): LogRecordProcessorInterface
    {
        return match (count($this->processors)) {
            0 => NoopLogRecordProcessor::getInstance(),
            1 => $this->processors[0],
            default => new MultiLogRecordProcessor($this->processors),
        };
    }
}
