<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Uid\Uuid;

/**
 * Stores Symfony UUIDs as readable CHAR(36) strings instead of binary.
 */
final class UuidType extends Type
{
    public function getName(): string
    {
        return 'uuid';
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = 36;
        $column['fixed'] = true;

        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof Uuid) {
            return (string) $value;
        }
        if (is_string($value)) {
            return $value;
        }

        throw ConversionException::conversionFailed($value, 'uuid');
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Uuid
    {
        if ($value === null || $value instanceof Uuid) {
            return $value;
        }
        try {
            return Uuid::fromString($value);
        } catch (\Throwable) {
            throw ConversionException::conversionFailed($value, 'uuid');
        }
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
