<?php
//http://stackoverflow.com/questions/4307407/magento-checking-if-a-module-is-installed
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Shell
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require_once 'abstract.php';

/**
 * Magento Compiler Shell Script
 *
 * @category    Mage
 * @package     Mage_Shell
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Shell_Dev extends Mage_Shell_Abstract
{
     private $local;
     private $stores;
     private $websites;
     private $storeViews;

     private $codePool = array('core', 'local', 'community');
     

     public function _construct()
     {
    	$xml = simplexml_load_file(__DIR__ . '/../app/etc/local.xml');
    	$this->local = $xml;
    	$this->getScopes();
     }

    public function getModules()
    {
        return Mage::getConfig()->getNode('modules')->children();
    }

    public function getSessionConfig()
    {
        return (string)$this->local->global->session_save;
    }

    public function getCacheConfig()
    {
        $cache = (string)$this->local->global->cache->backend;
        if(isset($cache) && !empty($cache)){
            return $cache;
        }

        return 'files';
    }

    public function getAdminUrl()
    {
        return (string)Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $this->local->admin->routers->adminhtml->args->frontName;
    }


    public function getScopes()
    {
        foreach (Mage::app()->getWebsites() as $website) {
            $this->websites++;
        	foreach ($website->getGroups() as $group) {
                $this->stores++;
        		$stores = $group->getStores();
        		foreach ($stores as $store) {
                    $this->storeViews++;
        		}
        	}
        }
    }

    private function getProductsCount()
    {
        return Mage::getModel('catalog/product')->getCollection()->getSize();
    }

    private function getCategoriesCount()
    {
        return Mage::getModel('catalog/category')->getCollection()->getSize();
    }

    private function getCustomersCount()
    {
        return Mage::getModel('customer/customer')->getCollection()->getSize();
    }

    private function getOrdersCount()
    {
        return Mage::getModel('sales/order')->getCollection()->getSize();
    }

    private function formatTitle($string)
    {
        return "\033[31m " . $string . " \033[0m ";
    }

    private function formatSubtitle($string)
    {
        return "\t \033[32m " . $string . "\033[0m ";
    }

     /**
     * Run script
     *
     */
    public function run()
    {
        if (isset($this->_args['status'])) {
            $modules = $this->getModules();
            echo $this->formatTitle("General Information:") . " \n";
            echo $this->formatSubtitle("Admin URL: ") . $this->getAdminUrl() . " \n";

            echo $this->formatSubtitle("Products: ") . $this->getProductsCount() . " \n";
            echo $this->formatSubtitle("Categories: ") . $this->getCategoriesCount() . " \n";
            echo $this->formatSubtitle("Customers: ") . $this->getCustomersCount() . " \n";
            echo $this->formatSubtitle("Orders: ") . $this->getOrdersCount() . " \n";

            echo "-------------------------------" . " \n";

            echo $this->formatTitle("Technical Information:") . " \n";
            echo $this->formatSubtitle("Session Saved in: ") . $this->getSessionConfig() . " \n";
            echo $this->formatSubtitle("Cache used: ") . $this->getCacheConfig() . " \n";
            echo $this->formatSubtitle("Number of Websites: ") . $this->websites . " \n";
            echo $this->formatSubtitle("Number of Stores: ") . $this->stores . " \n";
            echo $this->formatSubtitle("Number of Store Views: ") . $this->storeViews . " \n";
            echo $this->formatSubtitle("Number of Extensions: ") . count($modules) . " \n";
        } elseif(isset($this->_args['list'])) {
            $argsKeys = array_keys($this->_args);
            if(isset($argsKeys[1]) && in_array($argsKeys[1], $this->codePool)){
                $this->listExtensions($argsKeys[1]);
            } else {
                $this->listExtensions();
            }
        } else {
            echo $this->usageHelp();
        }
    }

    public function listExtensions($codePoolSpecific = false)
    {
        $modules = $this->getModules();
        foreach ($modules as $module) {
            $codePool = trim((string)$module->codePool);
            $codePoolArray[$codePool][] = $module;
        }

        foreach ($codePoolArray as $pool => $extensions) {
            if(!empty($extensions) && isset($extensions)){
                if($codePoolSpecific){
                    if($pool == $codePoolSpecific){
                        echo $this->formatTitle("Code Pool: ") .$pool. " \n";
                        foreach ($extensions as $oneExtension) {
                            echo $this->formatSubTitle($oneExtension->getName()) . " \n";
                        }
                    }
                } else {
                    echo $this->formatTitle("Code Pool: ") .$pool. " \n";
                    foreach ($extensions as $oneExtension) {
                        echo $this->formatSubTitle($oneExtension->getName()) . " \n";
                    }
                }
            }
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f dev.php --[options]

  status        Show information about the system
  list          List extensions in Magento, append the code pool after this command for specific results
  disable-ext   Disable extension (DB change) adding --hard will attempt to disable the extension from its XML configuration
  enable-ext    Enable an extension

USAGE;
    }
}

$shell = new Mage_Shell_Dev();
$shell->run();

