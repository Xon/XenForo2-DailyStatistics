<?php

namespace SV\DailyStatistics\XF\Admin\Controller;

use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\View as ViewReply;
use SV\DailyStatistics\XF\Repository\Counters as CountersRepo;

/**
 * Class Index
 *
 * @package SV\DailyStatistics\XF\Admin\Controller
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
            /** @var CountersRepo $countersRepo */
            $countersRepo = $this->repository('XF:Counters');
            $reply->setParam('extendedStatistics', $countersRepo->getExtendedStatistics(false));
        }

        return $reply;
    }
}