<?php
namespace SmallFry\lib;
/**
 * Create and get AppModel objects
 *
 * @author nlubin
 */
class AppModelFactory {
    /**
     * @var AppModel[]
     */
    private static $appModels = array();
    /**
     *
     * @param string $modelName
     * @param Config $CONFIG
     * @param MySQL_Interface $firstHandle
     * @param MySQL_Interface $secondHandle
     * @return AppModel 
     */
    static public function &buildModel($modelName, $CONFIG, $firstHandle, $secondHandle = null, $reset = false) {
        if(!isset(self::$appModels[$modelName]) || $reset)    {
            $model = true;
            self::$appModels[$modelName] = &$model;    //to make sure we do not get stuck in a nested loop DEFINE IT
            $namespacedModel = 'SmallFry\Model\\'.$modelName;
            if(class_exists($namespacedModel) && is_subclass_of($namespacedModel, __NAMESPACE__.'\AppModel')){
                /**  @var AppModel self::$appModels[$modelName] */
                $model = new $namespacedModel($CONFIG, $firstHandle, $secondHandle);
            }
            else {
                //default model (no database table chosen)
                $model = new AppModel($CONFIG, $firstHandle);
                $model->setModelName($modelName);
            }
        }
        return self::$appModels[$modelName];
    }

    static public function allModels(){
        return self::$appModels;
    }
}
