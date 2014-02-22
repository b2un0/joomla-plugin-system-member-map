<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @copyright  (c) 2014 Branko Wilhelm
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

final class MemberMapAdapterKunena implements MemberMapAdapterInterface
{

    protected $params;

    public function __construct(JRegistry &$params)
    {
        $this->params = $params;
    }

    public function getUsers()
    {
        if (!JComponentHelper::isEnabled('com_kunena')) {
            return JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_SYSTEM_MEMBERMAP_SOURCE_NOT_AVAILABLE', 'Kunena'), 'error');
        }

        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('u.id')
            ->from('#__kunena_users AS ku')
            ->join('INNER', '#__users AS u ON(u.id = ku.userid)')
            ->where('u.block = 0')
            ->where('ku.location != ' . $db->quote(''));

        if ($usergroups = $this->params->get('usergroup')) {
            $query->join('INNER', '#__user_usergroup_map AS g ON(u.id = g.user_id)');
            $query->where('g.group_id IN(' . implode(',', $usergroups) . ')');
        }

        switch ($this->params->get('order', 'name')) {
            default:
            case 'name';
                $query->order('u.name');
                break;

            case 'username':
                $query->order('u.username');

                break;

            case 'userid':
                $query->order('u.id');
                break;

            case 'location':
                $query->order('ku.location');
                break;

            case 'random':
                $query->order('RAND()');
                break;
        }

        $db->setQuery($query);

        $members = $db->loadColumn();

        if (empty($members)) {
            return null;
        }

        $users = array();
        foreach ($members as $key => &$member) {
            $member = KunenaFactory::getUser($member);
            $users[$key] = new stdClass;
            $users[$key]->name = $member->getName();
            $users[$key]->address = $member->location;
            $users[$key]->url = $member->getURL();
            $users[$key]->requests = 0;
            $users[$key]->ready = false;

            if ($this->params->get('avatar', 1)) {
                $users[$key]->avatar = $member->getAvatarURL();
                if (!filter_var($users[$key]->avatar, FILTER_VALIDATE_URL)) {
                    $users[$key]->avatar = JUri::root() . $users[$key]->avatar;
                }
            }
        }

        return $users;
    }

    // TODO

    public function onAfterRoute()
    {
        $app = JFactory::getApplication();
        $input = $app->input;

        if ($input->getCmd('option') == 'com_kunena' && $input->getCmd('view') == 'user') {
            // jQuery('#kprofile a[href*="maps.google.com"]').attr('href', '/*TODO*/');
        }
    }

}