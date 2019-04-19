<?php

namespace FiradioPHP\System;

use FiradioPHP\F;

/**
 * Description of config
 *
 * @author asheng
 */
class Config {

    private $aClass = array();
    private $aConfigs = array();

    public function __get($name) {
        if ($name === 'aClass') {
            return $this->aClass;
        }
        F::error("cant get property-name=$name", 'Error In FiradioPHP\System\Config');
    }

    public function __set($name, $value) {
        F::error("cant set property-name=$name", 'Error In FiradioPHP\System\Config');
    }

    public function __construct($configDir) {
        if (is_array($configDir)) {
            // 可载入多个配置文件夹，后面覆盖前面的配置
            foreach ($configDir as $k => $dir) {
                if (!is_dir($dir)) {
                    F::error('not exist configDir[' . $k . ']');
                    return FALSE;
                }
                $this->loadConfig($dir);
            }
        } else if (is_string($configDir)) {
            if (!is_dir($configDir)) {
                F::error('not exist configDir');
                return FALSE;
            }
            $this->loadConfig($configDir);
        } else {
            F::error('wrong type in configDir');
            return;
        }
        foreach ($this->aConfigs as $name => $aConfig) {
            $this->loadClass($name, $aConfig);
        }
    }

    private function loadConfig($configDir) {
        $dh = \opendir($configDir);
        if (!$dh) {
            return;
        }
        while (($file = \readdir($dh)) !== false) {
            if ($file === '..' || $file === '.') {
                // 跳过一些..或.的文件夹
                continue;
            }
            $path = $configDir . DS . $file;
            if (!is_file($path)) {
                // 跳过文件夹，只要文件
                continue;
            }
            $matches = array();
            if (preg_match('/^([A-Za-z][0-9a-z_]+)\.php$/', $file, $matches)) {
                $name = $matches[1]; // 配置名称
                $aConfig = include($path); // 配置文件内容
                if (is_callable($aConfig)) {
                    //针对一些return为function的配置需要执行后取得设置，例如db
                    $aConfig = $aConfig();
                }
                if (!isset($this->aConfigs[$name])) {
                    // 同名配置的第一个配置文件
                    $this->aConfigs[$name] = $aConfig;
                    continue;
                }
                // 同名配置的其他配置文件（覆盖掉前面的配置）
                $this->array_merge($this->aConfigs[$name], $aConfig);
            }
        }
        closedir($dh);
    }

    private function array_merge(&$a1, $a2) {
        // 通过递归方式将子数组也放进去
        foreach ($a2 as $k => $v) {
            if (is_array($v)) {
                $this->array_merge($a1[$k], $v);
                continue;
            }
            $a1[$k] = $v;
        }
    }

    public function getInstance($sName) {
        //取得一个新实例，或则已经标记为free的空闲实例
        if (!isset($this->aClass[$sName])) {
            F::error('The sName ' . $sName . ' does not exist in the $this->aClass');
            return;
        }
        $aClassInfo = &$this->aClass[$sName];
        $oInstance = array_pop($aClassInfo['free']);
        if ($oInstance === NULL) {
            //F::debug('new ' . $sName);
            $oInstance = new $aClassInfo['class']($aClassInfo['config']);
        }
        if (preg_match('/^db[0-9]+$/', $sName) || preg_match('/^db_[a-z]+$/', $sName)
        ) {
            if (!$oInstance->inTransaction()) {
                $iFreeCount = count($aClassInfo['free']);
                //F::info($sName . '->beginTransaction FreeCount=' . $iFreeCount);
                $oInstance->beginTransaction();
            }
        }
        return $oInstance;
    }

    public function freeInstance($sName, $oInstance) {
        //将实例记为free空闲状态
        $aClassInfo = &$this->aClass[$sName];
        $iFreeCount = array_push($aClassInfo['free'], $oInstance);
        if (preg_match('/^db[0-9]+$/', $sName)) {
            if ($oInstance->inTransaction()) {
                //F::info($sName . '->rollback FreeCount=' . $iFreeCount);
                $oInstance->rollback();
            }
        }
        return $iFreeCount;
    }

    private function loadClass($sName, $aConfig) {
        if (empty($aConfig['class'])) {
            F::error('empty class in config=' . $sName);
            return;
        }
        if (!class_exists($aConfig['class'])) {
            F::error('The class does not exist in the ' . $aConfig['class']);
            return;
        }
        if (in_array($sName, array('router', 'log'))) {
            $class = $aConfig['class'];
            F::$aInstances[$sName] = new $class($aConfig);
        }
        $aClassInfo = array();
        $aClassInfo['class'] = $aConfig['class'];
        $aClassInfo['config'] = $aConfig;
        $aClassInfo['free'] = array();
        $this->aClass[$sName] = $aClassInfo;
    }

}
