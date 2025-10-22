<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class JsonToArrayTransformer implements DataTransformerInterface
{
    /**
     * Transforms an array to a JSON string.
     */
    public function transform($value): string
    {
        if (null === $value) {
            return '';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return '';
    }

    /**
     * Transforms a JSON string to an array.
     */
    public function reverseTransform($value): ?array
    {
        if (!$value || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TransformationFailedException(
                'Invalid JSON: ' . json_last_error_msg()
            );
        }

        return $decoded;
    }
}