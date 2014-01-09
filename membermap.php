<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @copyright  (c) 2014 Branko Wilhelm
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

class plgSystemMemberMap extends JPlugin
{
    protected $loaded = false;

    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        if (JString::strpos($row->text, '{membermap}') === false) {
            return true;
        }

        $this->loadLanguage();

        if ($this->loaded == true) {
            JFactory::getApplication()->enqueueMessage(JText::_('PLG_SYSTEM_MEMBERMAP_ONLY_ONE_INSTANCE'), 'error');
        } else {
            $this->initMap();
            $this->loaded = true;
        }

        $row->text = JString::str_ireplace('{membermap}', '<div id="membermap"></div>', $row->text);
    }

    protected function initMap()
    {
        $method = 'getUsers' . $this->params->get('source');

        if (!method_exists($this, $method)) {
            return $this->message(JText::_('PLG_SYSTEM_MEMBERMAP_NO_SOURCE'), 'warning');
        }

        $users = call_user_func(array($this, $method));

        if (empty($users)) {
            return $this->message(JText::_('PLG_SYSTEM_MEMBERMAP_NO_USERS'), 'warning');
        }

        $doc = JFactory::getDocument();

        if ($this->params->get('key')) {
            $this->js .= '&amp;key=' . $this->params->get('key');
        }

        $doc->addScript('//maps.googleapis.com/maps/api/js?sensor=false');

        if ($this->params->get('cluster', 1)) {
            $doc->addScript('//google-maps-utility-library-v3.googlecode.com/svn/tags/markerclusterer/1.0.2/src/markerclusterer_compiled.js');
        }

        $doc->addScript('media/membermap/membermap.js');

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
        $config->cluster = $this->params->get('cluster', 1) ? true : false;

        $js[] = 'window.membermap.config = ' . json_encode($config);

        $doc->addScriptDeclaration(implode(';', $js));
    }

    private function message($msg, $type = 'message')
    {
        $msg = JText::_('PLG_SYSTEM_MEMBERMAP_NAME') . ': ' . $msg;
        JFactory::getApplication()->enqueueMessage($msg, $type);
    }

    protected function onAfterRoute()
    {
        $app = JFactory::getApplication();
        $input = $app->input;

        if ($input->getCmd('option') == 'com_kunena' && $input->getCmd('view') == 'user') {
            // jquery '#kprofile a[href*="maps.google.com"]'
        }
    }

    private function getUsersKunena()
    {
        if (!JComponentHelper::isEnabled('com_kunena')) {
            return $this->message(JText::sprintf('PLG_SYSTEM_MEMBERMAP_SOURCE_NOT_AVAILABLE', 'Kunena'), 'error');
        }

        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('u.id')
            ->from('#__kunena_users AS ku')
            ->join('INNER', '#__users AS u ON(u.id = ku.userid)')
            ->where('u.block = 0')
            ->where('ku.location != ' . $db->quote(''));

        $db->setQuery($query);

        $members = $db->loadColumn();

        if (empty($members)) {
            return null;
        }

        foreach ($members as &$member) {
            $member = KunenaFactory::getUser($member);
            $users[$member->userid] = new stdClass;
            $users[$member->userid]->name = $member->getName();
            $users[$member->userid]->address = $member->location;
            $users[$member->userid]->url = $member->getURL();
            $users[$member->userid]->requests = 0;
            $users[$member->userid]->ready = false;

            if ($this->params->get('avatar', 1)) {
                $users[$member->userid]->avatar = $member->getAvatarURL(); // TODO
                if (!filter_var($users[$member->userid]->avatar, FILTER_VALIDATE_URL)) {
                    $users[$member->userid]->avatar = JUri::root() . $users[$member->userid]->avatar;
                }
            }
        }

        return $users;
    }
}