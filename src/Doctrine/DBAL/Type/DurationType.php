<?php

declare(strict_types=1);

namespace Andante\PeriodBundle\Doctrine\DBAL\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateIntervalType;
use Doctrine\DBAL\Types\Exception\InvalidType;
use League\Period\Duration;

class DurationType extends DateIntervalType
{
    public const NAME = 'duration';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Duration
    {
        /** @var \DateInterval|null $value */
        $value = parent::convertToPHPValue($value, $platform);
        if (null === $value) {
            return null;
        }

        return Duration::createFromDateInterval($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        try {
            return parent::convertToDatabaseValue($value, $platform);
        } catch (ConversionException $e) {
            if (class_exists(InvalidType::class)) {
                throw InvalidType::new($value, $this->getName(), ['null', Duration::class], $e);
            }

            if (class_exists(ConversionException::class) &&
                method_exists(ConversionException::class, 'conversionFailedInvalidType')
            ) {
                throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', Duration::class], $e);
            }
        }

        return null;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
