<?php

namespace App\Support;

/**
 * How a branch runs.
 *
 * `autonomous` is the baseline and is what Dodoma is: a branch that does
 * everything, including bottles, oil fragrance and bottle accessories.
 *
 * `products_based` is a products-only branch, and is what Head Quarters-
 * Mikocheni is: its stock manager works with product stock alone. The bottles,
 * oil fragrance and bottle accessories screens are closed to it.
 *
 * This replaces the branch-name check that used to decide that rule. The name
 * still matters elsewhere — Kinondoni keeps cross-branch stock monitoring and
 * the two HQ roles keep the company mailbox — because those are not categories,
 * they are properties of those two particular branches. See BranchAccess.
 */
class BranchCategory
{
    /** Works like Dodoma: every stock module is available. */
    public const AUTONOMOUS = 'autonomous';

    /** Works like Head Quarters-Mikocheni: product stock only. */
    public const PRODUCTS_BASED = 'products_based';

    /**
     * Every valid category, in the order operators see them.
     *
     * @return string[]
     */
    public static function all(): array
    {
        return [self::AUTONOMOUS, self::PRODUCTS_BASED];
    }

    /**
     * The category to show in a select or a table cell.
     */
    public static function label(?string $category): string
    {
        return match ($category) {
            self::PRODUCTS_BASED => 'Products-only branch',
            self::AUTONOMOUS => 'Autonomous branch',
            default => 'Autonomous branch',
        };
    }

    /** One line under the select, explaining what the choice does. */
    public static function description(?string $category): string
    {
        return match ($category) {
            self::PRODUCTS_BASED => 'Product stock only — bottles, oil fragrance and bottle accessories are hidden.',
            default => 'Full branch — product stock, bottles, oil fragrance and bottle accessories.',
        };
    }

    public static function isValid(?string $category): bool
    {
        return in_array($category, self::all(), true);
    }

    /**
     * The stored value for a branch row, with anything unrecognised — including
     * a null from a database that has not had the column added yet — read as the
     * baseline.
     */
    public static function from(?string $category): string
    {
        return self::isValid($category) ? $category : self::AUTONOMOUS;
    }
}
