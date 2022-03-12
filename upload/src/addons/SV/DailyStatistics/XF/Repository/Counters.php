<?php

namespace SV\DailyStatistics\XF\Repository;

use XF\Finder\User as UserFinder;
use function is_callable, is_string, in_array;

/**
 * Class Counters
 *
 * @package SV\DailyStatistics\Repository
 */
class Counters extends XFCP_Counters
{
    /**
     * @return array
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function getForumStatisticsCacheData()
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
                $callable = $funcOptions[0];
                if (is_string($callable))
                {
                    $callable = [$this, $callable];
                }
                if (!is_callable($callable))
                {
                    continue;
                }
                $time1 = $funcOptions[1] ?? 0;
                $time2 = $funcOptions[2] ?? 0;
                $forumStatisticsCacheData['svDailyStatistics'][$statisticType][$type] = $callable($time1 ? $getTimestamp($time1) : 0, $time2 ? $getTimestamp($time2) : 0);
            }
        }

        return $forumStatisticsCacheData;
    }

    public function getExtendForumStatisticsDefinition(): array
    {
        $definition = [
            'latestUsers' => [
                'today' => ['getUserCountForDailyStatistics', 1],
                'week'  => ['getUserCountForDailyStatistics', 7],
                'month' => ['getUserCountForDailyStatistics', 30],
            ],
            'activeUsers' => [
                'today' => ['getUserCountForDailyStatistics', 0, 1],
                'week'  => ['getUserCountForDailyStatistics', 0, 7],
                'month' => ['getUserCountForDailyStatistics', 0, 30],
            ],
            'threads'     => [
                'today' => ['getThreadCountForDailyStatistics', 1],
                'week'  => ['getThreadCountForDailyStatistics', 7],
                'month' => ['getThreadCountForDailyStatistics', 30],
            ],
            'posts'       => [
                'today' => ['getPostCountForDailyStatistics', 1],
                'week'  => ['getPostCountForDailyStatistics', 7],
                'month' => ['getPostCountForDailyStatistics', 30],
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

        if (\XF::isAddOnActive('SV/Threadmarks', 2000000))
        {
            $definition['threadmarks'] = [
                'today' => ['getThreadmarkCountForDailyStatistics', 1],
                'week'  => ['getThreadmarkCountForDailyStatistics', 7],
                'month' => ['getThreadmarkCountForDailyStatistics', 30],
            ];
        }

        return $definition;
    }

    protected function getUserCountForDailyStatistics(int $registeredSince = 0, int $hasBeenActiveSince = 0): int
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

    protected function getThreadCountForDailyStatistics(int $startDate = 0): int
    {
        return $this->finder('XF:Thread')
                    ->where('discussion_state', 'visible')
                    ->where('post_date', '>=', $startDate)
                    ->total();
    }

    protected function getPostCountForDailyStatistics(int $startDate): int
    {
        return $this->finder('XF:Post')
                    ->where('message_state', 'visible')
                    ->where('post_date', '>=', $startDate)
                    ->total();
    }

    protected function getResourceCountForDailyStatistics(int $startDate): int
    {
        return $this->finder('XFRM:ResourceItem')
                    ->where('resource_state', 'visible')
                    ->where('resource_date', '>=', $startDate)
                    ->total();
    }

    protected function getMediaCountForDailyStatistics(int $startDate): int
    {
        return $this->finder('XFMG:MediaItem')
                    ->where('media_state', 'visible')
                    ->where('media_date', '>=', $startDate)
                    ->total();
    }

    protected function getThreadmarkCountForDailyStatistics(int $startDate): int
    {
        return $this->finder('SV\Threadmarks:Threadmark')
                    ->where('message_state', 'visible')
                    ->where('threadmark_date', '>=', $startDate)
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