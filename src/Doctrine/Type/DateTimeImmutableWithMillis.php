<?php

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

class DateTimeImmutableWithMillis extends Type
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return 'TIMESTAMP(3) WITHOUT TIME ZONE';
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s.v');
        }

        throw new ConversionException(sprintf('Expected \DateTimeInterface or null, got %s', get_debug_type($value)));
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): \DateTimeInterface|\DateTimeImmutable|null
    {
        if (null === $value || $value instanceof \DateTimeInterface) {
            return $value;
        }

        $val = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.v', $value);

        if (!$val) {
            $val = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        }

        if (!$val) {
            throw new ConversionException(sprintf('Could not convert database value "%s" to %s', get_debug_type($value), $this->getName()));
        }

        return $val;
    }

    public function getName(): string
    {
        return 'datetime_immutable_ms';
    }
}
