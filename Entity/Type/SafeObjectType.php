<?php

namespace JMS\JobQueueBundle\Entity\Type;;

use Doctrine\DBAL\Platforms\AbstractPlatform;

use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use function is_resource;
use function restore_error_handler;
use function serialize;
use function set_error_handler;
use function stream_get_contents;
use function unserialize;

/**
 * Type that maps a PHP object to a blob SQL type.
 *
 * Copy of the deprecated ObjectType, but with a different name and type.
 * Added for compatibility with JMSJobQueueBundle with DBAL 3.
 */
class SafeObjectType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBlobTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        return serialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = is_resource($value) ? stream_get_contents($value) : $value;

        set_error_handler(function (int $code, string $message): bool {
            throw new ConversionException(sprintf(
                "Could not convert database value to '%s' as an error was triggered by the unserialization: '%s'",
                'jms_job_safe_object', $message));
        });

        try {
            return unserialize($value);
        } finally {
            restore_error_handler();
        }
    }

    public function getName(): string
    {
        return 'jms_job_safe_object';
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
