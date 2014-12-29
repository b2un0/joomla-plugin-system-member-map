<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @copyright  (c) 2014 - 2015 Branko Wilhelm
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

final class MemberMapAdapterJomSocial implements MemberMapAdapterInterface
{
    protected $params;

    public function __construct(JRegistry $params)
    {
        $this->params = $params;
    }

    public function getUsers()
    {
        if (!JComponentHelper::isEnabled('com_community')) {
            JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_SYSTEM_MEMBERMAP_SOURCE_NOT_ENABLED', 'JomSocial'), 'error');
            return false;
        }

        JLoader::register('CFactory', JPATH_ROOT . '/components/com_community/libraries/core.php');

        $config = CFactory::getConfig();

        $db = JFactory::getDbo();

        // preselection of users with city
        $query = $db->getQuery(true)
            ->select('DISTINCT u.id')
            ->from('#__community_fields AS f')
            ->join('INNER', '#__community_fields_values AS fv ON(f.id = fv.field_id)')
            ->join('INNER', '#__users AS u ON(u.id = fv.user_id)')
            ->where('u.block = ' . $db->quote(0))
            ->where('f.published = ' . $db->quote(1))
            ->where('f.fieldcode = ' . $db->quote($config->get('fieldcodecity')))
            ->where('fv.value != ' . $db->quote(''));

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
                $query->order('fv.value');
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

        JFactory::getLanguage()->load('com_community.country');

        $users = array();
        foreach ($members as $key => $member) {
            $member = CFactory::getUser($member);

            $location = array(
                $member->getInfo($config->get('fieldcodestreet')),
                $member->getInfo($config->get('fieldcodecity')),
                $member->getInfo($config->get('fieldcodecountry'))
            );

            $location = array_map(array('JText', '_'), $location); // replace country names
            $location = array_filter($location); // remove empty array elements

            $users[$key] = new stdClass;
            $users[$key]->name = $member->getDisplayName();
            $users[$key]->address = implode(', ', $location); // build location string for google maps geocoder
            $users[$key]->url = CRoute::_('index.php?option=com_community&view=profile&userid=' . $member->id);
            $users[$key]->requests = 0;
            $users[$key]->ready = false;

            if ($this->params->get('avatar', 1)) {
                $users[$key]->avatar = $member->getThumbAvatar();
                if (!filter_var($users[$key]->avatar, FILTER_VALIDATE_URL)) {
                    $users[$key]->avatar = JUri::root() . $users[$key]->avatar;
                }
            }
        }

        return $users;
    }
}