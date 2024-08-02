<?php

namespace SV\DailyStatistics\XF\Admin\Controller;

use XF\Mvc\Reply\AbstractReply;
use XF\Searcher\User as UserSearcher;
use function end;
use function in_array;
use function strlen;

/**
 * @extends \XF\Admin\Controller\User
 */
class User extends XFCP_User
{
    protected static $allowedDayRange = [1, 7, 30];

    public function actionLatest(): AbstractReply
    {
        $order = $this->filter('order', 'str', 'register_date');
        $direction = $this->filter('direction', 'str');
        $days = (int)$this->filter('days', 'int');
        if (!in_array($days, static::$allowedDayRange, true))
        {
            $days = end(static::$allowedDayRange);
        }

        $page = $this->filterPage();
        $perPage = 25;

        /** @var UserSearcher $searcher */
        $searcher = $this->searcher('XF:User');
        if (strlen($order) !== 0 && strlen($direction) === 0)
        {
            $direction = $searcher->getRecommendedOrderDirection($order);
        }

        $searcher->setOrder($order, $direction);

        $finder = $searcher->getFinder();
        $finder->isValidUser();
        $finder->where('register_date', '>=', \XF::$time - $days * 86400);
        $total = $finder->total();

        $finder->limitByPage($page, $perPage);
        $users = $finder->fetch();

        $this->assertValidPage($page, $perPage, $total, 'users/latest');

        $linkParams = [
            'days' => $days,
            'direction' => $direction,
            'order' => $order,
        ];

        $viewParams = [
            'users' => $users,
            'days'  => $days,

            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'linkParams' => $linkParams,

            'criteria'    => $searcher->getFilteredCriteria(),
            'sortOptions' => $searcher->getOrderOptions(),
            'order'       => $order,
            'direction'   => $direction
        ];

        return $this->view('SV\DailyStatistics\XF:User\Latest', 'svDailyStatistics_latest_users', $viewParams);
    }
}