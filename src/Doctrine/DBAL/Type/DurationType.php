<?php

declare(strict_types=1);

namespace Andante\PeriodBundle\Doctrine\DBAL\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;
use League\Period\Duration;

class DurationType extends Type
{
    public const FORMAT = '%RP%YY%MM%DDT%HH%IM%SS';
    public const NAME = 'duration';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = 255;

        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        try {
            return $this->convertDateIntervalToDatabaseValue($value, $platform);
        } catch (ConversionException $e) {
            if (\class_exists(InvalidType::class)) {
                throw InvalidType::new($value, $this->getName(), ['null', Duration::class], $e);
            }

            if (\class_exists(ConversionException::class)
                && \method_exists(ConversionException::class, 'conversionFailedInvalidType')
            ) {
                throw InvalidType::new($value, $this->getName(), ['null', Duration::class], $e);
            }
        }

        return null;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Duration
    {
        /** @var \DateInterval|null $value */
        $value = $this->convertDateIntervalToPHPValue($value, $platform);
        if (null === $value) {
            return null;
        }

        return Duration::fromDateInterval($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }

    private function convertDateIntervalToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof \DateInterval) {
            return $value->format(self::FORMAT);
        }

        throw InvalidType::new($value, static::class, ['null', \DateInterval::class]);
    }

    /**
     * @param T $value
     *
     * @return (T is null ? null : \DateInterval)
     *
     * @template T
     */
    private function convertDateIntervalToPHPValue(mixed $value, AbstractPlatform $platform): ?\DateInterval
    {
        if (null === $value || $value instanceof \DateInterval) {
            return $value;
        }

        $negative = false;

        if (isset($value[0]) && ('+' === $value[0] || '-' === $value[0])) {
            $negative = '-' === $value[0];
            $value = \substr($value, 1);
        }

        try {
            $interval = new \DateInterval($value);

            if ($negative) {
                $interval->invert = 1;
            }

            return $interval;
        } catch (\Throwable $exception) {
            throw InvalidFormat::new($value, static::class, self::FORMAT, $exception);
        }
    }
}
