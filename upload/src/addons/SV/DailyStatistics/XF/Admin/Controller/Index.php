<?php

namespace SV\DailyStatistics\XF\Admin\Controller;

use XF\Repository\Counters as CountersRepo;

/**
 * Class Index
 *
 * @package SV\DailyStatistics\XF\Admin\Controller
 */
class Index extends XFCP_Index
{
    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionIndex()
    {
        /** @var CountersRepo $countersRepo */
        $countersRepo = $this->repository('XF:Counters');
        $countersRepo->rebuildForumStatisticsCache();

        return parent::actionIndex();
    }
}