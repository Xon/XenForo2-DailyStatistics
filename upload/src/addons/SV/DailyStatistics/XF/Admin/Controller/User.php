<?php

namespace SV\DailyStatistics\XF\Admin\Controller;

use XF\Mvc\Reply\AbstractReply;
use XF\Searcher\User as UserSearcher;
use function strlen;

/**
 * Class User
 *
 * @package SV\DailyStatistics\XF\Admin\Controller
 */
class User extends XFCP_User
{
    public function actionLatest(): AbstractReply
    {
        $order = $this->filter('order', 'str');
        $direction = $this->filter('direction', 'str');

        $page = $this->filterPage();
        $perPage = 20;

        /** @var UserSearcher $searcher */
        $searcher = $this->searcher('XF:User');
        if (strlen($order) !== 0 && strlen($direction) === 0)
        {
            $direction = $searcher->getRecommendedOrderDirection($order);
        }

        $searcher->setOrder($order, $direction);

        $finder = $searcher->getFinder();
        $finder->isValidUser();
        $finder->where('register_date', '>=', \XF::$time - 86400);
        $finder->limitByPage($page, $perPage);

        $total = $finder->total();
        $users = $finder->fetch();

        $this->assertValidPage($page, $perPage, $total, 'users/latest');

        $viewParams = [
            'users' => $users,

            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,


            'criteria'    => $searcher->getFilteredCriteria(),
            'sortOptions' => $searcher->getOrderOptions(),
            'order'       => $order,
            'direction'   => $direction
        ];

        return $this->view('SV\DailyStatistics\XF:User\Latest', 'svDailyStatistics_latest_users', $viewParams);
    }
}