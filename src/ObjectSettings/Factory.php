<?php

namespace srag\Plugins\H5P\ObjectSettings;

use ilH5PPlugin;
use ilObjH5P;
use ilObjH5PGUI;
use srag\DIC\H5P\DICTrait;
use srag\Plugins\H5P\Utils\H5PTrait;

/**
 * Class Factory
 *
 * @package srag\Plugins\H5P\ObjectSettings
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
final class Factory
{

    use DICTrait;
    use H5PTrait;
    const PLUGIN_CLASS_NAME = ilH5PPlugin::class;
    /**
     * @var self|null
     */
    protected static $instance = null;


    /**
     * @return self
     */
    public static function getInstance()/* : self*/
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * Factory constructor
     */
    private function __construct()
    {

    }


    /**
     * @return ObjectSettings
     */
    public function newInstance()/* : ObjectSettings*/
    {
        $object_settings = new ObjectSettings();

        return $object_settings;
    }


    /**
     * @param ilObjH5PGUI $parent
     * @param ilObjH5P    $object
     *
     * @return ObjectSettingsFormGUI
     */
    public function newFormInstance(ilObjH5PGUI $parent, ilObjH5P $object)/*:ObjectSettingsFormGUI*/
    {
        $form = new ObjectSettingsFormGUI($parent, $object);

        return $form;
    }
}
