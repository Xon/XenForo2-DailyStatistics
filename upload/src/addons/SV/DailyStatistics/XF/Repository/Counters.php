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

        $forumStatisticsCacheData['svDailyStatistics'] = [
            'latestUsers' => [
                'today' => $this->getUsersCountForDailyStatistics($getTimestamp(1)),
                'week' => $this->getUsersCountForDailyStatistics($getTimestamp(7)),
                'month' => $this->getUsersCountForDailyStatistics($getTimestamp(30))
            ],
            'activeUsers' => [
                'today' => $this->getUsersCountForDailyStatistics(0, $getTimestamp(1)),
                'week' => $this->getUsersCountForDailyStatistics(0, $getTimestamp(7)),
                'month' => $this->getUsersCountForDailyStatistics(0, $getTimestamp(30))
            ],
            'threads' => [
                'today' => $this->getThreadsCountForDailyStatistics($getTimestamp(1)),
                'week' => $this->getThreadsCountForDailyStatistics($getTimestamp(7)),
                'month' => $this->getThreadsCountForDailyStatistics($getTimestamp(30))
            ],
            'posts' => [
                'today' => $this->getPostsCountForDailyStatistics($getTimestamp(1)),
                'week' => $this->getPostsCountForDailyStatistics($getTimestamp(7)),
                'month' => $this->getPostsCountForDailyStatistics($getTimestamp(30))
            ]
        ];

        $addOns = \XF::app()->container('addon.cache');
        if (isset($addOns['XFRM']) && $addOns['XFRM'] >= 2000010)
        {
            $forumStatisticsCacheData['svDailyStatistics']['resources'] = [
                'today' => $this->getResourceCountForDailyStatistics($getTimestamp(1)),
                'week' => $this->getResourceCountForDailyStatistics($getTimestamp(7)),
                'month' => $this->getResourceCountForDailyStatistics($getTimestamp(30)),
            ];
        }

        if (isset($addOns['XFRM']) && $addOns['XFRM'] >= 2000010)
        {
            $forumStatisticsCacheData['svDailyStatistics']['mediaItems'] = [
                'today' => $this->getMediaCountForDailyStatistics($getTimestamp(1)),
                'week' => $this->getMediaCountForDailyStatistics($getTimestamp(7)),
                'month' => $this->getMediaCountForDailyStatistics($getTimestamp(30)),
            ];
        }

        return $forumStatisticsCacheData;
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
            ->where('post_date', $startDate)
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
}