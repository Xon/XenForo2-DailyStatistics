<?php

namespace SV\DailyStatistics;

use SV\Utils\InstallerHelper;
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
    // from https://github.com/Xon/XenForo2-Utils cloned to src/addons/SV/Utils
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

        $this->renameOption('dailystats_acp_extended','svDailyStatistics_extendedStatsInDashboard');
        /** @var \XF\Entity\Option $option */
        $option = \XF::finder('XF:Option')
                     ->whereId('svDailyStatistics_publicWidgetStatistics')
                     ->fetchOne();
        if (!$option)
        {
            $option = \XF::em()->create('XF:Option');
            $option->option_id = 'svDailyStatistics_publicWidgetStatistics';
            $option->setOption('verify_validation_callback', false);
            $option->setOption('verify_value', false);
            $option->addon_id = $this->addOn->getAddOnId();
            $option->edit_format = 'template';
            $option->edit_format_params = 'option_template_svDailyStatistics_dashboardStatistics';
            $option->data_type = 'array';
            $option->sub_options = ['*'];
        }
        $option->option_value = $extend;
        $option->saveIfChanged();

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
        /** @var \XF\Entity\Option $option */
        $option = \XF::finder('XF:Option')
                     ->whereId('svDailyStatistics_dashboardStatistics')
                     ->fetchOne();
        if (!$option)
        {
            $option = \XF::em()->create('XF:Option');
            $option->option_id = 'svDailyStatistics_dashboardStatistics';
            $option->setOption('verify_validation_callback', false);
            $option->setOption('verify_value', false);
            $option->addon_id = $this->addOn->getAddOnId();
            $option->edit_format = 'template';
            $option->edit_format_params = 'option_template_svDailyStatistics_dashboardStatistics';
            $option->data_type = 'array';
            $option->sub_options = ['*'];
        }
        $option->option_value = $extend;
        $option->saveIfChanged();
    }

    protected function hasOptionSet($optionName)
    {
        $option = \XF::finder('XF:Option')
                     ->whereId($optionName)
                     ->fetchOne();
        if ($option)
        {
            return (bool)$option->option_value;
        }

        return false;
    }

    /**
     * @param array $stateChanges
     */
    public function postInstall(array &$stateChanges)
    {
        /** @var CountersRepo $countersRepo */
        $countersRepo = \XF::repository('XF:Counters');
        $countersRepo->rebuildForumStatisticsCache();
    }

    /**
     * @param       $previousVersion
     * @param array $stateChanges
     */
    public function postUpgrade($previousVersion, array &$stateChanges)
    {
        /** @var CountersRepo $countersRepo */
        $countersRepo = \XF::repository('XF:Counters');
        $countersRepo->rebuildForumStatisticsCache();
    }
}