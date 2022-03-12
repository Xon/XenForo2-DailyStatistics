<?php

namespace SV\DailyStatistics;

use SV\StandardLib\InstallerHelper;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Repository\Counters as CountersRepo;

/**
 * Class Setup
 *
 * @package SV\DailyStatistics
 */
class Setup extends AbstractSetup
{
    use InstallerHelper;
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function upgrade2000001Step1()
    {
        $this->renamePermission(
            'MWSDailyStats', 'MWSviewStats',
            'general', 'svViewExtraStats'
        );

        $extend = [];
        if ($this->hasOptionSet('dailystats_fh_users'))
        {
            $extend[] = 'latestUsers';
        }
        if ($this->hasOptionSet('dailystats_fh_discussions'))
        {
            $extend[] = 'threads';
        }
        if ($this->hasOptionSet('dailystats_fm_messages'))
        {
            $extend[] = 'posts';
        }
        if ($this->hasOptionSet('dailystats_fh_resource'))
        {
            $extend[] = 'resources';
        }
        if ($this->hasOptionSet('dailystats_fh_xfmg'))
        {
            $extend[] = 'mediaItems';
        }

        $this->renameOption('dailystats_acp_extended', 'svDailyStatistics_extendedStatsInDashboard');
        $option = \XF::finder('XF:Option')
                     ->whereId('svDailyStatistics_publicWidgetStatistics')
                     ->fetchOne();
        if ($option === null)
        {
            /** @var \XF\Entity\Option $option */
            $option = \XF::em()->create('XF:Option');
            $option->option_id = 'svDailyStatistics_publicWidgetStatistics';
            if ($option->hasBehavior('XF:DevOutputWritable'))
            {
                $option->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', false);
            }
            $option->setOption('verify_validation_callback', false);
            $option->setOption('verify_value', false);
            $option->addon_id = $this->addOn->getAddOnId();
            $option->edit_format = 'template';
            $option->edit_format_params = 'option_template_svDailyStatistics_dashboardStatistics';
            $option->data_type = 'array';
            $option->sub_options = ['*'];
            $option->option_value = $extend;
            $option->save();
        }

        // ACP extend option
        $extend = [];
        if ($this->hasOptionSet('dailystats_acp_users'))
        {
            $extend[] = 'activeUsers';
            $extend[] = 'latestUsers';
        }
        if ($this->hasOptionSet('dailystats_acp_discussions'))
        {
            $extend[] = 'threads';
        }
        if ($this->hasOptionSet('dailystats_acp_messages'))
        {
            $extend[] = 'posts';
        }
        if ($this->hasOptionSet('dailystats_acp_resource'))
        {
            $extend[] = 'resources';
        }
        if ($this->hasOptionSet('dailystats_acp_xfmg'))
        {
            $extend[] = 'mediaItems';
        }

        $this->renameOption('dailystats_forum_home','svDailyStatistics_showInForumStatisticsWidget');
        $option = \XF::finder('XF:Option')
                     ->whereId('svDailyStatistics_dashboardStatistics')
                     ->fetchOne();
        if ($option === null)
        {
            /** @var \XF\Entity\Option $option */
            $option = \XF::em()->create('XF:Option');
            $option->option_id = 'svDailyStatistics_dashboardStatistics';
            if ($option->hasBehavior('XF:DevOutputWritable'))
            {
                $option->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', false);
            }
            $option->setOption('verify_validation_callback', false);
            $option->setOption('verify_value', false);
            $option->addon_id = $this->addOn->getAddOnId();
            $option->edit_format = 'template';
            $option->edit_format_params = 'option_template_svDailyStatistics_dashboardStatistics';
            $option->data_type = 'array';
            $option->sub_options = ['*'];
            $option->option_value = $extend;
            $option->save();
        }
    }

    protected function hasOptionSet($optionName): bool
    {
        /** @var \XF\Entity\Option $option */
        $option = \XF::finder('XF:Option')
                     ->whereId($optionName)
                     ->fetchOne();
        if ($option)
        {
            return (bool)$option->option_value;
        }

        return false;
    }

    public function upgrade2010000Step1()
    {
        $this->renamePhrases([
            'svDailyStatistics_new_media_items_today' => 'svDailyStatistics_new_today.mediaItems',
            'svDailyStatistics_new_members_today' => 'svDailyStatistics_new_today.latestUsers',
            'svDailyStatistics_new_posts_today' => 'svDailyStatistics_new_today.posts',
            'svDailyStatistics_new_resources_today' => 'svDailyStatistics_new_today.resources',
            'svDailyStatistics_new_resourcess_today' => 'svDailyStatistics_new_today.resources',
            'svDailyStatistics_new_threadmarks_today' => 'svDailyStatistics_new_today.threadmarks',
            'svDailyStatistics_new_threads_today' => 'svDailyStatistics_new_today.threads',
        ]);
    }

    public function upgrade2010000Step2()
    {
        @unlink(__DIR__.'/icon.png');
    }

    public function upgrade2010000Step3()
    {
        /** @var \XF\Entity\Option $option */
        $option = \XF::finder('XF:Option')
                     ->whereId('svDailyStatistics_dashboardStatistics')
                     ->fetchOne();
        if ($option !== null)
        {
            $extend = (array)($option->option_value ?? []);
            $extend[] = 'threadmarks';

            $option->option_value = $extend;
            $option->save();
        }
    }

    public function postInstall(array &$stateChanges)
    {
        /** @var CountersRepo $countersRepo */
        $countersRepo = \XF::repository('XF:Counters');
        $countersRepo->rebuildForumStatisticsCache();
    }

    /**
     * @param int|null $previousVersion
     * @param array $stateChanges
     */
    public function postUpgrade($previousVersion, array &$stateChanges)
    {
        /** @var CountersRepo $countersRepo */
        $countersRepo = \XF::repository('XF:Counters');
        $countersRepo->rebuildForumStatisticsCache();
    }
}