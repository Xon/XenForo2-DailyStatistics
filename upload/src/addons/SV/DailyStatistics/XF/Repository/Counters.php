<?php

namespace SV\DailyStatistics\XF\Repository;

use XF\Finder\User as UserFinder;
use XF\Finder\Thread as ThreadFinder;
use XF\Finder\Post as PostFinder;
use XFRM\Finder\ResourceItem as ResourceItemFinder;
use XFMG\Finder\MediaItem as MediaItemFinder;
use function is_callable, is_array, in_array;

/**
 * Class Counters
 *
 * @package SV\DailyStatistics\Repository
 */
class Counters extends XFCP_Counters
{
    public function getForumStatisticsCacheData(): array
    {
        /** @var array $forumStatisticsCacheData */
        $forumStatisticsCacheData = parent::getForumStatisticsCacheData();

        $getTimestamp = function ($days) {
            return \XF::$time - $days * 86400;
        };

        $definition = $this->getExtendForumStatisticsDefinition();
        foreach ($definition as $statisticType => $stats)
        {
            foreach ($stats as $type => $funcOptions)
            {
                $func = $funcOptions[0];
                $time1 = $funcOptions[1] ?? 0;
                $time2 = $funcOptions[2] ?? 0;
                $callable = is_callable($func) || is_array($func) ? $func : [$this, $func];
                $forumStatisticsCacheData['svDailyStatistics'][$statisticType][$type] = $callable($time1 ? $getTimestamp($time1) : 0, $time2 ? $getTimestamp($time2) : 0);
            }
        }

        return $forumStatisticsCacheData;
    }

    public function getExtendForumStatisticsDefinition(): array
    {
        $definition = [
            'latestUsers' => [
                'today' => ['getUsersCountForDailyStatistics', 1],
                'week'  => ['getUsersCountForDailyStatistics', 7],
                'month' => ['getUsersCountForDailyStatistics', 30],
            ],
            'activeUsers' => [
                'today' => ['getUsersCountForDailyStatistics', 0, 1],
                'week'  => ['getUsersCountForDailyStatistics', 0, 7],
                'month' => ['getUsersCountForDailyStatistics', 0, 30],
            ],
            'threads'     => [
                'today' => ['getThreadsCountForDailyStatistics', 1],
                'week'  => ['getThreadsCountForDailyStatistics', 7],
                'month' => ['getThreadsCountForDailyStatistics', 30],
            ],
            'posts'       => [
                'today' => ['getPostsCountForDailyStatistics', 1],
                'week'  => ['getPostsCountForDailyStatistics', 7],
                'month' => ['getPostsCountForDailyStatistics', 30],
            ]
        ];

        if (\XF::isAddOnActive('XFRM', 2000010))
        {
            $definition['resources'] = [
                'today' => ['getResourceCountForDailyStatistics', 1],
                'week'  => ['getResourceCountForDailyStatistics', 7],
                'month' => ['getResourceCountForDailyStatistics', 30],
            ];
        }

        if (\XF::isAddOnActive('XFMG', 2000010))
        {
            $definition['mediaItems'] = [
                'today' => ['getMediaCountForDailyStatistics', 1],
                'week'  => ['getMediaCountForDailyStatistics', 7],
                'month' => ['getMediaCountForDailyStatistics', 30],
            ];
        }

        return $definition;
    }

    protected function getUsersCountForDailyStatistics(int $registeredSince = 0, int $hasBeenActiveSince = 0): int
    {
        if ($registeredSince === 0 && $hasBeenActiveSince === 0)
        {
            return 0;
        }

        /** @var UserFinder $userFinder */
        $userFinder = $this->finder('XF:User');
        $userFinder->isValidUser();

        if ($registeredSince !== 0)
        {
            $userFinder->where('register_date', '>=', $registeredSince);
        }

        if ($hasBeenActiveSince !== 0)
        {
            $userFinder->where('last_activity', '>=', $hasBeenActiveSince);
        }

        return $userFinder->total();
    }

    protected function getThreadsCountForDailyStatistics(int $startDate = 0): int
    {
        /** @var ThreadFinder $threadFinder */
        $threadFinder = $this->finder('XF:Thread');

        return $threadFinder
            ->where('discussion_state', 'visible')
            ->where('post_date', '>=', $startDate)
            ->total();
    }

    protected function getPostsCountForDailyStatistics(int $startDate): int
    {
        /** @var PostFinder $postFinder */
        $postFinder = $this->finder('XF:Post');

        return $postFinder
            ->where('message_state', 'visible')
            ->where('post_date', '>=', $startDate)
            ->total();
    }

    protected function getResourceCountForDailyStatistics(int $startDate): int
    {
        /** @var ResourceItemFinder $resourceItemFinder */
        $resourceItemFinder = $this->finder('XFRM:ResourceItem');

        return $resourceItemFinder
            ->where('resource_state', 'visible')
            ->where('resource_date', '>=', $startDate)
            ->total();
    }

    protected function getMediaCountForDailyStatistics(int $startDate): int
    {
        /** @var MediaItemFinder $mediaItemFinder */
        $mediaItemFinder = $this->finder('XFMG:MediaItem');

        return $mediaItemFinder
            ->where('media_state', 'visible')
            ->where('media_date', '>=', $startDate)
            ->total();
    }

    public function getExtendedStatistics(bool $public, bool $applyPermissions = true, bool $hideDisabled = true): array
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

        $options = $this->app()->options();
        $dashboardStatistics = $public
            ? ($options->svDailyStatistics_publicWidgetStatistics ?? null)
            : ($options->svDailyStatistics_dashboardStatistics ?? null);
        if ($dashboardStatistics === null)
        {
            return [];
        }

        $extendedStatistics = [];
        $forumStatistics = $this->app()->get('forumStatistics');

        $definition = $this->getExtendForumStatisticsDefinition();
        foreach ($definition as $statisticType => $stats)
        {
            if (!$public && $applyPermissions &&
                in_array($statisticType, ['latestUsers', 'activeUsers'], true) &&
                !$visitor->hasAdminPermission('user')
            )
            {
                continue;
            }

            if ($hideDisabled && !in_array($statisticType, $dashboardStatistics, true))
            {
                continue;
            }

            $statistics = $forumStatistics['svDailyStatistics'][$statisticType] ?? [
                    'today' => 0,
                    'week'  => 0,
                    'month' => 0,
                ];

            $extendedStatistics[$statisticType] = [
                'label' => \XF::phrase('svDailyStatistics_extended_stat.' . $statisticType),
                'stats' => $statistics
            ];
        }


        return $extendedStatistics;
    }
}