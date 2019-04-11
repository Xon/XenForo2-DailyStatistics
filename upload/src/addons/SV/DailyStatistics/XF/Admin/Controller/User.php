<?php

namespace SV\DailyStatistics\XF\Admin\Controller;

use XF\Searcher\User as UserSearcher;
use XF\Finder\User as UserFinder;

/**
 * Class User
 *
 * @package SV\DailyStatistics\XF\Admin\Controller
 */
class User extends XFCP_User
{
    /**
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionLatest()
    {
        $order = $this->filter('order', 'str');
        $direction = $this->filter('direction', 'str');

        $page = $this->filterPage();
        $perPage = 20;

        /** @var UserSearcher $searcher */
        $searcher = $this->searcher('XF:User');
        if ($order && !$direction)
        {
            $direction = $searcher->getRecommendedOrderDirection($order);
        }

        $searcher->setOrder($order, $direction);

        /** @var UserFinder $finder */
        $finder = $searcher->getFinder();
        $finder->isValidUser();
        $finder->where('register_date', '>', \XF::$time - 86401);
        $finder->limitByPage($page, $perPage);

        $total = $finder->total();
        $users = $finder->fetch();

        $this->assertValidPage($page, $perPage, $total, 'users/latest');

        $viewParams = [
            'users' => $users,

            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,


            'criteria' => $searcher->getFilteredCriteria(),
            'sortOptions' => $searcher->getOrderOptions(),
            'order' => $order,
            'direction' => $direction
        ];
        return $this->view(
            'SV\DailyStatistics\XF:User\Latest',
            'svDailyStatistics_latest_users',
            $viewParams
        );
    }
}