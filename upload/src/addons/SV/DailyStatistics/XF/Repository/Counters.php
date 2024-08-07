<?php

namespace SV\DailyStatistics\XF\Repository;

use SV\DailyStatistics\XF\Entity\User as ExtendedUserEntity;
use SV\StandardLib\Helper;
use SV\Threadmarks\Finder\Threadmark as ThreadmarkFinder;
use XF\Finder\Post as PostFinder;
use XF\Finder\Thread as ThreadFinder;
use XF\Finder\User as UserFinder;
use XFMG\Finder\MediaItem as MediaItemFinder;
use XFRM\Finder\ResourceItem as ResourceItemFinder;
use function array_keys;
use function array_merge;
use function count;
use function is_callable, is_string, in_array;

/**
 * @extends \XF\Repository\Counters
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

        $definition = $this->getExtendForumStatisticsDefinition(false, false);
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

    protected function svBuildRecentSearchLink(int $days, string $contentType, string $subtype = '', array $additionalCriteria = []): string
    {
        $search = [
            'keywords' => '*',
            'order' => 'date',
            'search_type' => $contentType,
            'c' => [
                'newer_than' => \XF::$time - $days * 86400,
            ],
        ];
        if ($subtype !== '')
        {
            $search['c']['content'] = $subtype;
        }
        if (count($additionalCriteria) !== 0)
        {
            $search = array_merge($search, $additionalCriteria);
        }

        return \XF::app()->router('public')->buildLink('search/search', null, $search);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function getExtendForumStatisticsDefinition(bool $public, bool $withSearch): array
    {
        $searchLink = $withSearch
            ? \Closure::fromCallable([$this, 'svBuildRecentSearchLink'])
            : function (int $days, string $contentType, string $subtype = '') { return ''; };

        $latestUserSearch = $withSearch && \XF::visitor()->hasAdminPermission('user')
            ? function (int $days) { return $this->app()->router('admin')->buildLink('users/latest', null, ['days' => $days]); }
            : function (int $days) { return ''; };

        $definition = [
            'activeUsers' => [
                'today' => ['getUserCountForDailyStatistics', 0, 1],
                'week'  => ['getUserCountForDailyStatistics', 0, 7],
                'month' => ['getUserCountForDailyStatistics', 0, 30],
            ],
            'latestUsers' => [
                'today' => ['getUserCountForDailyStatistics', 1, 'searchUrl' => $latestUserSearch(1)],
                'week'  => ['getUserCountForDailyStatistics', 7, 'searchUrl' => $latestUserSearch(7)],
                'month' => ['getUserCountForDailyStatistics', 30, 'searchUrl' => $latestUserSearch(30)],
            ],
            'threads'     => [
                'today' => ['getThreadCountForDailyStatistics', 1, 'searchUrl' => $searchLink(1, 'post', 'thread')],
                'week'  => ['getThreadCountForDailyStatistics', 7, 'searchUrl' => $searchLink(7, 'post', 'thread')],
                'month' => ['getThreadCountForDailyStatistics', 30, 'searchUrl' => $searchLink(30, 'post', 'thread')],
            ],
            'posts'       => [
                'today' => ['getPostCountForDailyStatistics', 1, 'searchUrl' => $searchLink(1, 'post')],
                'week'  => ['getPostCountForDailyStatistics', 7, 'searchUrl' => $searchLink(7, 'post')],
                'month' => ['getPostCountForDailyStatistics', 30, 'searchUrl' => $searchLink(30, 'post')],
            ]
        ];

        if (\XF::isAddOnActive('XFRM', 2000010))
        {
            $definition['resources'] = [
                'today' => ['getResourceCountForDailyStatistics', 1, 'searchUrl' => $searchLink(1, 'resource', 'resource')],
                'week'  => ['getResourceCountForDailyStatistics', 7, 'searchUrl' => $searchLink(7, 'resource', 'resource')],
                'month' => ['getResourceCountForDailyStatistics', 30, 'searchUrl' => $searchLink(30, 'resource', 'resource')],
            ];
        }

        if (\XF::isAddOnActive('XFMG', 2000010))
        {
            $definition['mediaItems'] = [
                'today' => ['getMediaCountForDailyStatistics', 1, 'searchUrl' => $searchLink(1, 'xfmg_media')],
                'week'  => ['getMediaCountForDailyStatistics', 7, 'searchUrl' => $searchLink(7, 'xfmg_media')],
                'month' => ['getMediaCountForDailyStatistics', 30, 'searchUrl' => $searchLink(30, 'xfmg_media')],
            ];
        }

        if (\XF::isAddOnActive('SV/Threadmarks', 2000000))
        {
            $threadmarkSearchLink = function (int $days) use ($searchLink) {
                return $searchLink($days, 'post', '', ['threadmark_only' => true]);
            };

            $definition['threadmarks'] = [
                'today' => ['getThreadmarkCountForDailyStatistics', 1, 'searchUrl' => $threadmarkSearchLink(1)],
                'week'  => ['getThreadmarkCountForDailyStatistics', 7, 'searchUrl' => $threadmarkSearchLink(7)],
                'month' => ['getThreadmarkCountForDailyStatistics', 30, 'searchUrl' => $threadmarkSearchLink(30)],
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

        $userFinder = Helper::finder(UserFinder::class);
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
        return Helper::finder(ThreadFinder::class)
                     ->where('discussion_state', 'visible')
                     ->where('post_date', '>=', $startDate)
                     ->total();
    }

    protected function getPostCountForDailyStatistics(int $startDate): int
    {
        return Helper::finder(PostFinder::class)
                     ->where('message_state', 'visible')
                     ->where('post_date', '>=', $startDate)
                     ->total();
    }

    protected function getResourceCountForDailyStatistics(int $startDate): int
    {
        return Helper::finder(ResourceItemFinder::class)
                     ->where('resource_state', 'visible')
                     ->where('resource_date', '>=', $startDate)
                     ->total();
    }

    protected function getMediaCountForDailyStatistics(int $startDate): int
    {
        return Helper::finder(MediaItemFinder::class)
                     ->where('media_state', 'visible')
                     ->where('media_date', '>=', $startDate)
                     ->total();
    }

    protected function getThreadmarkCountForDailyStatistics(int $startDate): int
    {
        return Helper::finder(ThreadmarkFinder::class)
                     ->where('message_state', 'visible')
                     ->where('threadmark_date', '>=', $startDate)
                     ->total();
    }

    public function getExtendedStatistics(bool $public, bool $applyPermissions = true, bool $hideDisabled = true): array
    {
        /** @var ExtendedUserEntity $visitor */
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

        $search = (
                      $public && (\XF::options()->svDailyStatisticsSearchLinkPublic ?? false) ||
                      !$public && (\XF::options()->svDailyStatisticsSearchLinkAdmin ?? true)
                  ) && $visitor->canSearch();

        $extendedStatistics = [];
        $forumStatistics = $this->app()->get('forumStatistics');

        $definition = $this->getExtendForumStatisticsDefinition($public, $search);
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
                'search' => [],
            ];

            if ($search)
            {
                foreach(array_keys($statistics) as $key)
                {
                    $url = $stats[$key]['searchUrl'] ?? null;
                    if ($url !== null)
                    {
                        $statistics['search'][$key] = $url;
                    }
                }
            }

            $extendedStatistics[$statisticType] = $statistics;
        }


        return $extendedStatistics;
    }
}