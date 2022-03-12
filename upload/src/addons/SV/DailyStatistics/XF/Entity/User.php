<?php

namespace SV\DailyStatistics\XF\Entity;

/**
 * Class User
 *
 * @package SV\DailyStatistics\XF\Entity
 */
class User extends XFCP_User
{
    /**
     * @param \XF\Phrase|string|null $error
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public function canViewDailyStatistics(&$error = null): bool
    {
        return $this->hasPermission('general', 'svViewExtraStats');
    }
}