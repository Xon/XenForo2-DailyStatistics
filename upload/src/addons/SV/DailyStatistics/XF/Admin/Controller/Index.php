<?php

namespace SV\DailyStatistics\XF\Admin\Controller;

use XF\Mvc\Reply\View;
use SV\DailyStatistics\XF\Repository\Counters as CountersRepo;

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
        $reply =  parent::actionIndex();
        $visitor = \XF::visitor();

        if ($reply instanceof View && $visitor->hasAdminPermission('viewStatistics'))
        {
            /** @var CountersRepo $countersRepo */
            $countersRepo = $this->repository('XF:Counters');
            $countersRepo->rebuildForumStatisticsCache();

            $reply->setParam('extendedStatistics', $countersRepo->getExtendedStatistics());
        }

        return $reply;
    }
}