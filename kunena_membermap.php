<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @copyright  (c) 2014 Branko Wilhelm
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die();

class plgSystemKunena_MemberMap extends JPlugin
{
    private $js = '//maps.googleapis.com/maps/api/js?sensor=false';

    private $search = array('{kunena_membermap}', '{kunena_member_map}');

    private $load = false;

    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        if (JString::strpos($row->text, $this->search) === false || !JComponentHelper::isEnabled('com_kunena')) {
            return true;
        }

        if ($this->load == true) {
            $replace = __CLASS__ . ': only one instance per site allowed';
        } else {
            $replace = $this->initMap();
        }

        $row->text = JString::str_ireplace($this->search, '<div id="membermap"></div>', $row->text);

        $this->load = true;
    }

    protected function initMap()
    {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();
        $doc = JFactory::getDocument();

        $doc->addScript($this->js);
        $doc->addScript('media/kunena_membermap/membermap.js');
        $doc->addStyleSheet('media/kunena_membermap/membermap.css');

        $query = $db->getQuery(true)
            ->select('u.id')
            ->from('#__kunena_users AS ku')
            ->join('INNER', '#__users AS u ON(u.id = ku.userid)')
            ->where('u.block = 0')
            ->where('ku.location != ' . $db->quote(''));

        $db->setQuery($query);

        $members = $db->loadColumn();

        if (empty($members)) {
            return JText::_('PLG_SYSTEM_KUNENA_MEMBERMAP_NO_USER_LOCATIONS');
        }

        foreach ($members as &$member) {
            $member = KunenaFactory::getUser($member);
            $json[$member->userid] = new stdClass;
            $json[$member->userid]->name = $member->getName();
            $json[$member->userid]->address = $member->location;
            $json[$member->userid]->avatar = $member->getAvatarURL();
            $json[$member->userid]->url = $member->getURL();

            if (!filter_var($json[$member->userid]->avatar, FILTER_VALIDATE_URL)) {
                $json[$member->userid]->avatar = JUri::root() . $json[$member->userid]->avatar;
            }
        }

        $doc->addScriptDeclaration('window.membermap.users = ' . json_encode($json) . ';');
    }

    /**
     * replace on index.php?option=com_kunena&view=user&userid=
     */
    public function onAfterRoute()
    {
        $app = JFactory::getApplication();
        $input = $app->input;

        if ($input->getCmd('option') == 'com_kunena' && $input->getCmd('view') == 'user') {
            // jquery '#kprofile a[href*="maps.google.com"]'
        }
    }
}