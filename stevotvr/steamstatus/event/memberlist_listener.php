<?php
/**
 *
 * Steam Status. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steve Guidetti, https://github.com/stevotvr
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace stevotvr\steamstatus\event;

use \phpbb\cache\service;
use \phpbb\config\config;
use \phpbb\controller\helper;
use \phpbb\event\data;
use \phpbb\language\language;
use \phpbb\template\template;
use \stevotvr\steamstatus\operator\steamprofile_interface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Steam Status listener for memberlist events.
 */
class memberlist_listener implements EventSubscriberInterface
{
	/**
	 * @var \phpbb\config\config
	 */
	private $config;

	/**
	 * @var \phpbb\controller\helper
	 */
	private $helper;

	/**
	 * @var \phpbb\language\language
	 */
	private $language;

	/**
	 * @var \stevotvr\steamstatus\operator\steamprofile_interface
	 */
	private $steamprofile;

	/**
	 * @var \phpbb\template\template
	 */
	private $template;

	/**
	 * @param \phpbb\config\config                                  $config
	 * @param \phpbb\controller\helper                              $helper
	 * @param \phpbb\language\language                              $language
	 * @param \stevotvr\steamstatus\operator\steamprofile_interface $steamprofile
	 * @param \phpbb\template\template                              $template
	 */
	function __construct(config $config, helper $helper, language $language, steamprofile_interface $steamprofile, template $template)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->language = $language;
		$this->steamprofile = $steamprofile;
		$this->template = $template;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.memberlist_view_profile'	=> 'memberlist_view_profile',
		);
	}

	/**
	 * Loads the Steam Status template variables for the user profile.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function memberlist_view_profile(data $event)
	{
		if (!$this->config['stevotvr_steamstatus_show_on_profile'] || empty($this->config['stevotvr_steamstatus_api_key']))
		{
			return;
		}

		$this->language->add_lang('common', 'stevotvr/steamstatus');
		$this->template->assign_vars(array(
			'U_STEAMSTATUS_CONTROLLER'	=> $this->helper->route('stevotvr_steamstatus_route'),
			'S_STEAMSTATUS'				=> true,
		));

		$steamid = $event['member']['user_steamid'];
		if (!empty($steamid))
		{
			$cached = $this->steamprofile->get_from_cache($steamid);
			if ($cached)
			{
				$this->template->assign_vars(array(
					'STEAMSTATUS_STEAMID'	=> $steamid,
					'STEAMSTATUS_NAME'		=> $cached->get_name(),
					'STEAMSTATUS_PROFILE'	=> $cached->get_profile(),
					'STEAMSTATUS_AVATAR'	=> $cached->get_avatar(),
					'S_STEAMSTATUS_SHOW'	=> true,
				));

				if (time() - $cached->get_querytime() < 60)
				{
					$this->template->assign_vars(array(
						'STEAMSTATUS_STATE'		=> $cached->get_state(),
						'STEAMSTATUS_STATUS'	=> $cached->get_localized_status(),
						'S_STEAMSTATUS_LOADED'	=> true,
					));
				}
			}
			else
			{
				$this->template->assign_vars(array(
					'STEAMSTATUS_STEAMID'	=> $steamid,
					'STEAMSTATUS_PROFILE'	=> 'http://steamcommunity.com/profiles/' . $steamid,
					'S_STEAMSTATUS_SHOW'	=> true,
				));
			}
		}
	}
}
