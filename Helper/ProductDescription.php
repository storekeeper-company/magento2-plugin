<?php

namespace StoreKeeper\StoreKeeper\Helper;

class ProductDescription
{
    const DISALLOWED_CONTENT = [
        '/<style\b[^>]*>([\s\S]*?)<\/style>/i',
        '/<figure\s+data-content-type="image"[^>]*>[\s\S]*?<\/figure>/i',
        '/{{widget[^}]*}}/i'
    ];

    /**
     * @param string|null $description
     * @return string|null
     */
    public function formatProductDescription(?string $description): ?string
    {
        if (!is_null($description)) {
            foreach (self::DISALLOWED_CONTENT as $pattern) {
                if (preg_match($pattern, $description)) {
                    $description = preg_replace($pattern, '', $description);
                }
            }
        }

        return $description;
    }

    /**
     * @param string $description
     * @return bool
     */
    public function isDisallowedContentExist(string $description): bool
    {
        foreach (self::DISALLOWED_CONTENT as $pattern) {
            if (preg_match($pattern, $description)) {
                return true;
            }
        }

        return false;
    }
}
