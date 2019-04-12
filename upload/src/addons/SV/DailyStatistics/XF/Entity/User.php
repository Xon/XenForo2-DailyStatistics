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
     * @param null $error
     *
     * @return bool
     */
    public function canViewDailyStatistics(&$error = null)
    {
        return $this->hasPermission('general', 'svViewExtraStats');
    }
}