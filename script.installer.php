<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @copyright  (c) 2014 - 2015 Branko Wilhelm
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

class plgSystemMembermapInstallerScript
{
    private $jversion = 3.2;

    public function preflight()
    {
        if (!version_compare(JVERSION, $this->jversion, '>=')) {
            $link = JHtml::_('link', 'index.php?option=com_joomlaupdate', 'Joomla! ' . $this->jversion);
            JFactory::getApplication()->enqueueMessage(sprintf('You need %s or newer to install this extension', $link), 'error');

            return false;
        }

        return true;
    }
}