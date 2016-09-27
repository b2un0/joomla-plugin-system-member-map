<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @copyright  (c) 2014 - 2015 Branko Wilhelm
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

class plgSystemMemberMap extends JPlugin
{
    protected $loaded = false;
    protected $adapter = null;

    public function onAjaxMembermap()
    {
        $input = JFactory::getApplication()->input;
        $key = $input->getString('key');
        $val = $input->getString('val');

        if (!empty($val)) {
            $val = explode(',', $val);
            $obj = new stdClass;
            $obj->lat = (float)filter_var($val[0], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $obj->lng = (float)filter_var($val[1], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $val = $obj;
        }

        return $this->handleLocation($key, $val);
    }

    protected function handleLocation($key, $val = null)
    {
        $cache = JFactory::getCache('membermap', 'output');
        $cache->setCaching(1);
        $cache->setLifeTime($this->params->get('cache_timeout', 24) * 60);

        $key = md5($key);

        if (empty($val)) {
            return $cache->get($key);
        } else {
            return $cache->store($val, $key);
        }
    }

    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        if (JString::strpos($row->text, '{membermap}') === false) {
            return true;
        }

        $this->loadLanguage();

        JLoader::discover('MemberMapAdapter', __DIR__ . '/adapters/');

        $class = 'MemberMapAdapter' . $this->params->get('source');

        if (class_exists($class)) {
            $this->adapter = new $class($this->params);
        } else {
            JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_SYSTEM_MEMBERMAP_SOURCE_NOT_AVAILABLE', $this->params->get('source')), 'error');
            return true;
        }

        if ($this->loaded == true) {
            JFactory::getApplication()->enqueueMessage(JText::_('PLG_SYSTEM_MEMBERMAP_ONLY_ONE_INSTANCE'), 'error');
            return true;
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
            JFactory::getApplication()->enqueueMessage(JText::_('PLG_SYSTEM_MEMBERMAP_NO_USERS'), 'warning');
            return false;
        }

        $doc = JFactory::getDocument();

        $query['sensor'] = $this->params->get('sensor') ? 'true' : 'false';

        if ($this->params->get('key')) {
            $query['key'] = $this->params->get('key');
        }

        $query = http_build_query($query);

        $doc->addScript('//maps.googleapis.com/maps/api/js?' . $query);

        if ($this->params->get('cluster')) {
            $doc->addScript('//cdnjs.cloudflare.com/ajax/libs/js-marker-clusterer/1.0.0/markerclusterer_compiled.js');
        }

        foreach ($users as $user) {
            if ($postion = $this->handleLocation($user->address)) {
                $user->position = $postion;
                $user->position->lat = (float)$user->position->lat;
                $user->position->lng = (float)$user->position->lng;
                $user->ready = true;
            }
        }

        $js[] = 'membermap.users = ' . json_encode($users);

        if ($this->params->get('legend', 1)) {
            $css[] = '#membermap_legend{max-height:' . ($this->params->get('height', 500) - 100) . 'px;}';
            $doc->addStyleDeclaration(implode($css));
            $doc->addStyleSheet('media/plg_system_membermap/membermap.css');
        }

        $config = new stdClass;
        $config->center = (int)$this->params->get('center', 2);
        $config->legend = $this->params->get('legend', 1) ? true : false;
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
        $config->base = JUri::base();

        $js[] = 'membermap.config = ' . json_encode($config);

        $doc->addScriptDeclaration(implode(';', $js));

        JHtml::_('jquery.framework');

        $doc->addScript('media/plg_system_membermap/membermap.js');
    }
}
