<?php

namespace SV\DailyStatistics\XF\Entity;

/**
 * @extends \XF\Entity\User
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