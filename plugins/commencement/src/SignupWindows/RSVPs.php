<?php

namespace DigraphCMS_Plugins\unmous\commencement\SignupWindows;

use DigraphCMS\DB\DB;
use DigraphCMS\Users\User;

class RSVPs
{
    /**
     * Get all RSVPs for a given array of NetIDs, optionally filtered by window
     *
     * @param array $netIDs
     * @param SignupWindow|null $window
     * @return RSVPSelect|null
     */
    public static function getForNetIDs(array $netIDs, SignupWindow $window = null): ?RSVPSelect
    {
        if (!count($netIDs)) return null;
        return static::select($window)
            ->where('for in (' . implode(',', array_map([DB::pdo(), 'quote'], $netIDs)) . ')');
    }

    /**
     * Get all RSVPs created by a given user, optionally filtered by window
     *
     * @param User $user
     * @param SignupWindow $window
     * @return RSVPSelect|null
     */
    public static function getByCreator(User $user, SignupWindow $window = null): RSVPSelect
    {
        return static::select($window)
            ->where('created_by', $user->uuid());
    }

    public static function get(?string $uuid, SignupWindow $window = null): ?RSVP
    {
        if (!$uuid) return null;
        return static::select($window)
            ->where('uuid = ?', [$uuid])
            ->fetch();
    }

    public static function select(SignupWindow $window = null): RSVPSelect
    {
        $select = new RSVPSelect(
            DB::query()->from('commencement_signup')
        );
        if ($window) $select->where('window = ?', [$window->uuid()]);
        return $select;
    }
}
