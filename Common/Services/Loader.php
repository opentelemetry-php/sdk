<?php

declare(strict_types=1);

namespace OpenTelemetry\SDK\Common\Services;

use Nevay\SPI\ServiceLoader;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use OpenTelemetry\SDK\Common\Export\TransportFactoryInterface;
use OpenTelemetry\SDK\Logs\LogRecordExporterFactoryInterface;
use OpenTelemetry\SDK\Metrics\MetricExporterFactoryInterface;
use OpenTelemetry\SDK\Propagation\TextMapPropagatorFactoryInterface;
use OpenTelemetry\SDK\Resource\ResourceDetectorFactoryInterface;
use OpenTelemetry\SDK\Resource\ResourceDetectorInterface;
use OpenTelemetry\SDK\Trace\SpanExporter\SpanExporterFactoryInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\SpanProcessorContext;
use OpenTelemetry\SDK\Trace\SpanProcessor\SpanProcessorFactoryInterface;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use RuntimeException;

/**
 * Loads services from SPI, handling type and priority.
 */
class Loader
{
    /**
     * @param class-string<SpiLoadableInterface> $class
     * @phan-suppress PhanTypeNonVarPassByRef
     */
    private static function getFactory(string $class, string $type): mixed
    {
        $factories = iterator_to_array(ServiceLoader::load($class));
        array_multisort(
            array_map(static fn ($factory) => $factory->priority(), $factories),
            SORT_DESC,
            $factories,
        );
        foreach ($factories as $factory) {
            if ($factory->type() === $type) {
                return $factory;
            }
        }

        return null;
    }

    public static function spanExporterFactory(string $exporter): SpanExporterFactoryInterface
    {
        return self::getFactory(SpanExporterFactoryInterface::class, $exporter)
            ?? throw new RuntimeException('Span exporter factory not defined for: ' . $exporter);
    }

    public static function logRecordExporterFactory(string $exporter): LogRecordExporterFactoryInterface
    {
        return self::getFactory(LogRecordExporterFactoryInterface::class, $exporter)
            ?? throw new RuntimeException('LogRecord exporter factory not defined for: ' . $exporter);
    }

    /**
     * Get transport factory registered for protocol. If $protocol contains a content-type eg `http/xyz` then
     * only the first part, `http`, is used.
     */
    public static function transportFactory(string $protocol): TransportFactoryInterface
    {
        $type = explode('/', $protocol)[0];

        return self::getFactory(TransportFactoryInterface::class, $type)
            ?? throw new RuntimeException('Transport factory not defined for protocol: ' . $type);
    }

    public static function metricExporterFactory(string $exporter): MetricExporterFactoryInterface
    {
        return self::getFactory(MetricExporterFactoryInterface::class, $exporter)
            ?? throw new RuntimeException('Metric exporter factory not registered for protocol: ' . $exporter);
    }

    public static function textMapPropagator(string $name): TextMapPropagatorInterface
    {
        $factory = self::getFactory(TextMapPropagatorFactoryInterface::class, $name)
            ?? throw new RuntimeException('Text map propagator not registered for: ' . $name);

        return $factory->create();
    }

    public static function resourceDetector(string $name): ResourceDetectorInterface
    {
        $factory = self::getFactory(ResourceDetectorFactoryInterface::class, $name)
            ?? throw new RuntimeException('Resource detector not registered for: ' . $name);

        return $factory->create();
    }

    /**
     * @return ResourceDetectorInterface[]
     */
    public static function resourceDetectors(): array
    {
        $factories = iterator_to_array(ServiceLoader::load(ResourceDetectorFactoryInterface::class));

        return array_map(fn (ResourceDetectorFactoryInterface $factory) => $factory->create(), $factories);
    }

    public static function spanProcessor(string $name, SpanProcessorContext $context): SpanProcessorInterface
    {
        $factory = self::getFactory(SpanProcessorFactoryInterface::class, $name)
            ?? throw new RuntimeException('Span processor not registered for: ' . $name);

        return $factory->create($context);
    }
}
