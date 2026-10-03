<?php

namespace App\Support;

/**
 * The two branches whose staff are not ordinary branch staff.
 *
 * Every capability gate in the app is a positive allowlist keyed on the branch
 * NAME, and each one names only these two. That is what makes Dodoma — named
 * by none of them — the baseline: a branch created tomorrow inherits Dodoma's
 * access purely by existing, with nothing to configure.
 *
 * The flip side is that these privileges are carried by the name rather than by
 * a flag, so a branch created with one of these names would silently inherit
 * them. BranchController refuses such a name for that reason.
 *
 * Nothing new may be added here without a decision of its own; a third
 * exception is a policy change, not a default.
 */
class BranchAccess
{
    /** The depot, and the only stock manager who may monitor other branches. */
    public const KINONDONI = 'Kinondoni branch';

    /** Runs the company mailbox, public inquiries and news moderation. */
    public const HEAD_QUARTERS = 'Head Quarters-Mikocheni';

    /**
     * The branches that hold an exception, in the order they are named to
     * operators.
     *
     * @return string[]
     */
    public static function names(): array
    {
        return [self::KINONDONI, self::HEAD_QUARTERS];
    }

    /** The same comparison the scopes use, so a guard cannot disagree with them. */
    public static function normalise(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    /** Would a branch with this name inherit an exception? */
    public static function isExceptionName(?string $name): bool
    {
        if ($name === null || trim($name) === '') {
            return false;
        }

        $needle = self::normalise($name);

        foreach (self::names() as $exception) {
            if (self::normalise($exception) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * The explanation shown to an operator who tries to delete one of the two
     * branches that carry a privilege.
     *
     * These two cannot be deleted, only renamed away from. The privilege is
     * keyed on the name, so deleting the branch would remove a company-wide
     * capability — cross-branch stock monitoring, or the company mailbox — and
     * leave nothing to reassign it to. A rename gives it up deliberately.
     */
    public static function deletionMessage(string $name): string
    {
        return "\"{$name}\" cannot be deleted. Branch names decide access, and this "
            . 'one carries a privilege the whole company depends on — either '
            . self::KINONDONI . '\'s cross-branch stock monitoring, or '
            . self::HEAD_QUARTERS . '\'s mailbox with news and inquiries moderation. '
            . 'Rename it to give that privilege up, and it can be deleted afterwards.';
    }

    /** The name to show an operator who has just tried to use an exception name. */
    public static function rejectionMessage(string $attempted): string
    {
        return "\"{$attempted}\" is reserved. It matches an existing branch, and branch "
            . 'names decide access: reusing one would hand this branch the privileges '
            . 'of ' . self::KINONDONI . ' or ' . self::HEAD_QUARTERS
            . '. Please choose a different name.';
    }
}
