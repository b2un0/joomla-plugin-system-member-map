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
    protected $adapter = null;

    public function __construct(&$subject, $config = array())
    {
        parent::__construct($subject, $config);

        $this->loadLanguage();

        JLoader::discover('MemberMapAdapter', dirname(__FILE__) . '/adapters/');

        $class = 'MemberMapAdapter' . $this->params->get('source');
        if (class_exists($class)) {
            $this->adapter = new $class($this->params);
        } else {
            return JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_SYSTEM_MEMBERMAP_SOURCE_NOT_AVAILABLE', $this->params->get('source')), 'error');
        }
    }

    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        if (JString::strpos($row->text, '{membermap}') === false) {
            return true;
        }

        if (!($this->adapter instanceof MemberMapAdapterInterface)) {
            return JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_SYSTEM_MEMBERMAP_SOURCE_NOT_AVAILABLE', $this->params->get('source')), 'error');
        }

        if ($this->loaded == true) {
            return JFactory::getApplication()->enqueueMessage(JText::_('PLG_SYSTEM_MEMBERMAP_ONLY_ONE_INSTANCE'), 'error');
        } else {
            $this->initMap();
            $this->loaded = true;
        }

        $row->text = JString::str_ireplace('{membermap}', '<div id="membermap"></div>', $row->text);
    }

    protected function initMap()
    {
        $users = $this->adapter->getUsers();

        if (empty($users)) {
            return JFactory::getApplication()->enqueueMessage(JText::_('PLG_SYSTEM_MEMBERMAP_NO_USERS'), 'warning');
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
        $config->center = (int)$this->params->get('center', 2);
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

    public function onAfterRoute()
    {
        $this->adapter->onAfterRoute();
    }
}