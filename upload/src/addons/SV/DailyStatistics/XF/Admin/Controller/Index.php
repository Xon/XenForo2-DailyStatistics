<?php

namespace SV\DailyStatistics\XF\Admin\Controller;

use SV\StandardLib\Helper;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\View as ViewReply;
use XF\Repository\Counters as CountersRepo;
use SV\DailyStatistics\XF\Repository\Counters as ExtendedCountersRepo;

/**
 * @extends \XF\Admin\Controller\Index
 */
class Index extends XFCP_Index
{
    /**
     * @return AbstractReply
     */
    public function actionIndex()
    {
        $reply = parent::actionIndex();

        if ($reply instanceof ViewReply && \XF::visitor()->hasAdminPermission('viewStatistics'))
        {
            /** @var ExtendedCountersRepo $countersRepo */
            $countersRepo = Helper::repository(CountersRepo::class);
            $reply->setParam('extendedStatistics', $countersRepo->getExtendedStatistics(false));
        }

        return $reply;
    }
}