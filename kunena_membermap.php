<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @copyright  (c) 2014 Branko Wilhelm
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

class plgSystemKunena_MemberMap extends JPlugin
{
    private $js = '//maps.googleapis.com/maps/api/js?sensor=false';
    private $search = '{kunena_membermap}';
    private $load = false;

    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        if (JString::strpos($row->text, $this->search) === false || !JComponentHelper::isEnabled('com_kunena')) {
            return true;
        }

        if ($this->load == true) {
            $replace = JText::_('PLG_SYSTEM_KUNENA_MEMBERMAP_ONLY_ONE_INSTANCE');
        } else {
            $replace = $this->initMap();
        }

        $row->text = JString::str_ireplace($this->search, $replace, $row->text);

        $this->load = true;
    }

    protected function initMap()
    {
        $db = JFactory::getDbo();
        $doc = JFactory::getDocument();

        if ($this->params->get('key')) {
            $this->js .= '&amp;key=' . $this->params->get('key');
        }

        $doc->addScript($this->js);
        $doc->addScript('//google-maps-utility-library-v3.googlecode.com/svn/tags/markerclusterer/1.0.2/src/markerclusterer_compiled.js');
        $doc->addScript('media/kunena_membermap/membermap.js');

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

        foreach ($members as $key => &$member) {
            $member = KunenaFactory::getUser($member);
            $users[$member->userid] = new stdClass;
            $users[$member->userid]->name = $member->getName();
            $users[$member->userid]->address = $member->location;
            $users[$member->userid]->avatar = $member->getAvatarURL();
            $users[$member->userid]->url = $member->getURL();
            $users[$member->userid]->requests = 0;
            $users[$member->userid]->ready = false;

            if (!filter_var($users[$member->userid]->avatar, FILTER_VALIDATE_URL)) {
                $users[$member->userid]->avatar = JUri::root() . $users[$member->userid]->avatar;
            }
        }

        $js[] = 'window.membermap.users = ' . json_encode($users);

        $config = new stdClass;
        $config->center = $this->params->get('center', 1) ? true : false;
        $config->bounce = $this->params->get('bounce', 1) ? true : false;
        $config->drop = $this->params->get('drop', 1) ? true : false;
        $config->delay = (int)$this->params->get('delay', 750);
        $config->width = $this->params->get('width', '100%');
        $config->height = (int)$this->params->get('height', 500);
        $config->type = strtoupper($this->params->get('type', 'ROADMAP'));
        $config->zoom = (int)$this->params->get('zoom', 1);
        $config->lat = (float)$this->params->get('lat', 42);
        $config->lng = (float)$this->params->get('lng', 11);
        $config->requests = (int)$this->params->get('requests', 3);
        $config->size = (int)$this->params->get('size', 30);

        $js[] = 'window.membermap.config = ' . json_encode($config);

        $doc->addScriptDeclaration(implode(';', $js));

        return '<div id="membermap"></div>';
    }

    // TODO

    public function onAfterRoute()
    {
        $app = JFactory::getApplication();
        $input = $app->input;

        if ($input->getCmd('option') == 'com_kunena' && $input->getCmd('view') == 'user') {
            // jquery '#kprofile a[href*="maps.google.com"]'
        }
    }
}