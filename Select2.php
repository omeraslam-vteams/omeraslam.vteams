<?php

/**
 * Wrapper Widget to use jQuery Select2 in Yii application.
 *
 * @author Muhammad Omer Aslam <omeraslam@nxb.com.pk>
 * @copyright Copyright &copy; 2014 http://nxb.com.pk
 * @package extensions
 * @subpackage select2
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @version 3.4.3 rev.0
 *
 * @see https://github.com/ivaynberg/select2 jQuery Select2
 */
class Select2 extends CInputWidget
{

    /** @var string Path to assets directory published in init() */
    private $assetsDir;

    /** @var array Chosen script settings passed to $.fn.chosen() */
    private $settings = array();

    /** @var bool Multiple or single item should be selected */
    public $multiple = false;

    /** @var array See CHtml::listData() */
    public $data;
    public $onTrigger = array();

    /** Initialize method to publish assets */
    public function init()
    {
        /*assets directory path*/
        $dir = dirname(__FILE__) . '/assets';
        $this->assetsDir = Yii::app()->assetManager->publish($dir);

        /*Check if multi-select or select one is being populated*/
        if ($this->multiple) {
            $this->htmlOptions['multiple'] = true;
        } elseif (isset($this->htmlOptions['multiple'])) {
            $this->multiple = true;
        }

        /*Check if placeholder is provided for the select 2*/
        if (isset($this->htmlOptions['placeholder'])) {
            $this->settings['placeholder'] = $this->htmlOptions['placeholder'];
        } elseif (isset($this->htmlOptions['data-placeholder'])) {
            $this->settings['placeholder'] = $this->htmlOptions['data-placeholder'];
        }

        /*check if select2 options are initialized*/
        if (isset($this->htmlOptions['select2Options'])) {
            /*check if on-triggers are specified for the select2*/
            if (array_key_exists("onTrigger", $this->htmlOptions['select2Options'])) {
                $this->onTrigger = CMap::mergeArray($this->onTrigger, $this->htmlOptions['select2Options']['onTrigger']);
                unset($this->htmlOptions['select2Options']['onTrigger']);
            }

            /*merge all options to a single array named settings and unset the default */
            $this->settings = CMap::mergeArray($this->settings, $this->htmlOptions['select2Options']);
            unset($this->htmlOptions['select2Options']);
        }

    }

    /**Update scripts and bind events */
    public function run()
    {
        list($name, $id) = $this->resolveNameID();

        /*if attribute id is provided for the select2*/

        if (isset($this->htmlOptions['id'])) {
            $id = $this->htmlOptions['id'];
        } else {
            $this->htmlOptions['id'] = $id;
        }

        /*if name has been provided for the select2*/

        if (isset($this->htmlOptions['name'])) {
            $name = $this->htmlOptions['name'];
        }

        /*if ajax has been initialized for select2 then populate the text field*/

        if (isset($this->settings['ajax'])) {

            /*if ajax set for the select2 then check if the model reference has
            been passed if YES then use model field value ELSE use default option value*/

            if (isset($this->model)) {
                echo CHtml::textField($name, $this->model->{$this->attribute}, $this->htmlOptions);
            } else {
                echo CHtml::textField($name, $this->value, $this->htmlOptions);
            }
        } else {
            /*If AJAX is not set for select2 then populate the select menu */
            if (isset($this->model)) {
                echo CHtml::dropDownList($name, $this->model->{$this->attribute}, $this->data, $this->htmlOptions);
            } else {
                echo CHtml::dropDownList($name, $this->value, $this->data, $this->htmlOptions);
            }
        }

        $this->registerScripts($id);
    }

    /** Register client scripts
     * @params $id    id of the select2 list to be populated
     */
    private function registerScripts($id)
    {
        /*GET CLIENT SCRIPT*/
        $cs = Yii::app()->getClientScript();

        /*Uncomment the following line if you have'nt enabled default jquery script in your site*/
        //$cs->registerCoreScript('jquery');

        /*user minified js source if the debugger is on*/

        $src = !YII_DEBUG ? '' : '/src';
        $min = YII_DEBUG ? '' : '.min';

        /*register CSS & JS files */

        $cs->registerCssFile($this->assetsDir . $src . '/select2' . $min . '.css');
        $cs->registerScriptFile($this->assetsDir . $src . '/select2' . $min . '.js');

        /*check if localization is needed*/
        $lang = strtoupper(str_replace('_', '-', Yii::app()->language));
        $lang[0] = strtolower($lang[0]);
        $lang[1] = strtolower($lang[1]);

        /*register language script file */
        $cs->registerScriptFile($this->assetsDir . $src . '/select2_locale_' . $lang . $min . '.js');

        /*Encode al the settings in the array to javascript array */
        $settings = CJavaScript::encode($this->settings);

        /*if on-trigger are provided then append necessary code provided for triggers*/

        $onTriggers = '';
        if (!empty($this->onTrigger)) {
            foreach ($this->onTrigger as $k => $v) {
                $onTriggers .= '.on(\'' . $k . '\',' . CJavaScript::encode($v) . ')';
            }
        }

        /*initialize the select2 */
        $cs->registerScript("{$id}_select2", ""
            . "$('#{$id}').select2({$settings})" . $onTriggers . ";"
        );

    }

    /**Populate normal (without model reference) Dropdown list
     * @params $name           The name for the select2 list
     * @params $select         The selected option in the drop down
     * @params $data           The data to be populated
     * @params $htmlOptions    The html options for the select2
     */

    public static function dropDownList($name, $select, $data, $htmlOptions = array())
    {
        return Yii::app()->getController()->widget(__CLASS__, array(
            'name' => $name,
            'value' => $select,
            'data' => $data,
            'htmlOptions' => $htmlOptions,
        ), true);
    }

    /**Populate active Dropdown list
     * @params $model          The name for the model used for select2 list
     * @params $attribute      The name attribute used for the select2 dropdown
     * @params $data           The data to be populated
     * @params $htmlOptions    The html options array for the select2
     */

    public static function activeDropDownList($model, $attribute, $data, $htmlOptions = array())
    {
        return self::dropDownList(CHtml::activeName($model, $attribute), CHtml::value($model, $attribute), $data, $htmlOptions);
    }

    /** Multiple select without model
     * @params $name           The name for the select2 list
     * @params $select         The selected option in the drop down
     * @params $data           The data to be populated
     * @params $htmlOptions    The html options for the select2
     */
    public static function multiSelect($name, $select, $data, $htmlOptions = array())
    {
        return Yii::app()->getController()->widget(__CLASS__, array(
            'name' => $name,
            'value' => $select,
            'data' => $data,
            'htmlOptions' => $htmlOptions,
            'multiple' => true,
        ), true);
    }

    /** Active Multiple select without model
     * @params $model          The name for the model used for select2 list
     * @params $attribute      The name attribute used for the select2 dropdown
     * @params $data           The data to be populated
     * @params $htmlOptions    The html options array for the select2
     */

    public static function activeMultiSelect($model, $attribute, $data, $htmlOptions = array())
    {
        return self::multiSelect(CHtml::activeName($model, $attribute) . '[]', CHtml::value($model, $attribute), $data, $htmlOptions);
    }

}
