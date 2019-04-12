<?php

namespace SV\DailyStatistics\XF\Repository;

use XF\Finder\User as UserFinder;
use XF\Finder\Thread as ThreadFinder;
use XF\Finder\Post as PostFinder;
use XFRM\Finder\ResourceItem as ResourceItemFinder;
use XFMG\Finder\MediaItem as MediaItemFinder;

/**
 * Class Counters
 *
 * @package SV\DailyStatistics\Repository
 */
class Counters extends XFCP_Counters
{
    /**
     * @return array|bool|false
     */
    public function getForumStatisticsCacheData()
    {
        $forumStatisticsCacheData = parent::getForumStatisticsCacheData();

        $getTimestamp = function ($days)
        {
            return \XF::$time - $days * 86400;
        };

        $definition = $this->getExtendForumStatisticsDefinition();
        foreach($definition as $statisticType => $stats)
        {
            foreach ($stats as $type => $funcOptions)
            {
                list ($func, $days) = $funcOptions;
                $callable = \is_callable($func) || is_array($func) ? $func : [$this, $func];
                $forumStatisticsCacheData['svDailyStatistics'][$statisticType][$type] = $callable($getTimestamp($days));
            }
        }

        return $forumStatisticsCacheData;
    }

    public function getExtendForumStatisticsDefinition()
    {
        $definition = [
            'latestUsers' => [
                'today' => ['getUsersCountForDailyStatistics', 1],
                'week' => ['getUsersCountForDailyStatistics', 7],
                'month' => ['getUsersCountForDailyStatistics', 30],
            ],
            'activeUsers' => [
                'today' => ['getUsersCountForDailyStatistics', 1],
                'week' => ['getUsersCountForDailyStatistics', 7],
                'month' => ['getUsersCountForDailyStatistics', 30],
            ],
            'threads' => [
                'today' => ['getThreadsCountForDailyStatistics', 1],
                'week' => ['getThreadsCountForDailyStatistics', 7],
                'month' => ['getThreadsCountForDailyStatistics', 30],
            ],
            'posts' => [
                'today' => ['getPostsCountForDailyStatistics', 1],
                'week' => ['getPostsCountForDailyStatistics', 7],
                'month' => ['getPostsCountForDailyStatistics', 30],
            ]
        ];

        $addOns = \XF::app()->container('addon.cache');
        if (isset($addOns['XFRM']) && $addOns['XFRM'] >= 2000010)
        {
            $definition['resources'] = [
                'today' => ['getResourceCountForDailyStatistics', 1],
                'week' => ['getResourceCountForDailyStatistics', 7],
                'month' => ['getResourceCountForDailyStatistics', 30],
            ];
        }

        if (isset($addOns['XFRM']) && $addOns['XFRM'] >= 2000010)
        {
            $definition['mediaItems'] = [
                'today' => ['getMediaCountForDailyStatistics', 1],
                'week' => ['getMediaCountForDailyStatistics', 7],
                'month' => ['getMediaCountForDailyStatistics', 30],
            ];
        }

        return $definition;
    }

    /**
     * @param int $registeredSince
     * @param int $hasBeenActiveSince
     *
     * @return int
     */
    protected function getUsersCountForDailyStatistics($registeredSince = 0, $hasBeenActiveSince = 0)
    {
        /** @var UserFinder $userFinder */
        $userFinder = $this->finder('XF:User');
        $userFinder->isValidUser();

        if ($registeredSince)
        {
            $userFinder->where('register_date', '>=', $registeredSince);
        }

        if ($hasBeenActiveSince)
        {
            $userFinder->where('last_activity', '>=', $hasBeenActiveSince);
        }

        return $userFinder->total();
    }

    /**
     * @param int $startDate
     *
     * @return int
     */
    protected function getThreadsCountForDailyStatistics($startDate = 0)
    {
        /** @var ThreadFinder $threadFinder */
        $threadFinder = $this->finder('XF:Thread');

        return $threadFinder
            ->where('discussion_state', 'visible')
            ->where('post_date', '>=', $startDate)
            ->total();
    }

    /**
     * @param int $startDate
     *
     * @return int
     */
    protected function getPostsCountForDailyStatistics($startDate)
    {
        /** @var PostFinder $postFinder */
        $postFinder = $this->finder('XF:Post');

        return $postFinder
            ->where('message_state', 'visible')
            ->where('post_date', '>=', $startDate)
            ->total();
    }

    /**
     * @param int $startDate
     *
     * @return int
     */
    protected function getResourceCountForDailyStatistics($startDate)
    {
        /** @var ResourceItemFinder $resourceItemFinder */
        $resourceItemFinder = $this->finder('XFRM:ResourceItem');

        return $resourceItemFinder
            ->where('resource_state', 'visible')
            ->where('resource_date', '>=', $startDate)
            ->total();
    }

    /**
     * @param int $startDate
     *
     * @return int
     */
    protected function getMediaCountForDailyStatistics($startDate)
    {
        /** @var MediaItemFinder $mediaItemFinder */
        $mediaItemFinder = $this->finder('XFMG:MediaItem');

        return $mediaItemFinder
            ->where('media_state', 'visible')
            ->where('media_date', '>=', $startDate)
            ->total();
    }

    /**
     * @param bool $public
     * @param bool $applyPermissions
     * @param bool $hideDisabled
     * @return array
     */
    public function getExtendedStatistics($public, $applyPermissions = true, $hideDisabled = true)
    {
        /** @var \SV\DailyStatistics\XF\Entity\User $visitor */
        $visitor = \XF::visitor();

        if ($applyPermissions)
        {
            if ($public)
            {
                if (!$visitor->canViewDailyStatistics())
                {
                    return [];
                }
            }
            else
            {
                if (!$visitor->hasAdminPermission('viewStatistics'))
                {
                    return [];
                }
            }
        }

        $key = $public ? 'svDailyStatistics_publicWidgetStatistics' : 'svDailyStatistics_dashboardStatistics';
        $options = $this->app()->options();
        if (!$options->offsetExists($key))
        {
            return [];
        }

        $dashboardStatistics = $options->offsetGet($key);
        $extendedStatistics = [];

        /** @noinspection PhpUndefinedFieldInspection */
        $forumStatistics = $this->app()->forumStatistics;

        $definition = $this->getExtendForumStatisticsDefinition();
        foreach ($definition as $statisticType => $stats)
        {
            $statistics = empty($forumStatistics['svDailyStatistics'][$statisticType])
                ? [
                    'today' => 0,
                    'week'  => 0,
                    'month' => 0,
                ]
                : $forumStatistics['svDailyStatistics'][$statisticType];

            if ($hideDisabled && !in_array($statisticType, $dashboardStatistics, true))
            {
                continue;
            }

            $extendedStatistics[$statisticType] = [
                'label' => \XF::phrase('svDailyStatistics_extended_stat.' . $statisticType),
                'stats' => $statistics
            ];
        }


        return $extendedStatistics;
    }
}