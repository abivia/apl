<?php
/**
 * Base classes to support AP5L_Forms_Rapid functionality.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Rapid.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 *
 * @todo complete phpdocs
 */

/**
 * Class to support a set of generic comment/mail-to forms.
 *
 * Use by creating the form as a session variable, optionally choosing a
 * standard form with preset(), defining additional fields, and then calling the
 * process() and generate() methods.
 *
 * This class supports the use of captcha plugins.
 * 
 * @package AP5L
 * @subpackage Forms
 */
class AP5L_Forms_Rapid extends AP5L_Php_InflexibleObject {
    /**
     * Set when the user has passed captcha test.
     * @var boolean
     */
    private $_captchaPassed;
    /**
     * Set when the form generates a confirmation step.
     * 
     * @var boolean 
     */
    private $_confirmStep = true;
    /**
     * Debugging flag, if true generate diagnostic output.
     * 
     * @var boolean 
     */
    private $_debug;
    /**
     * Saved error flag or error object.
     * 
     * @var boolean|object 
     */
    protected $_error = false;
    /**
     * List of the fields on this form.
     * 
     * @var AP5L_Forms_Field_Container 
     */
    protected $_fieldContainer = array();
    /**
     * Scratch area for first routing label.
     * 
     * @var string 
     *
     * When defining routes for the form (eg. sales, customer service, etc.)
     * this variable holds the value of the first field. If only one route is
     * specified, then no user selection control is created. If a second route
     * is created, then the control is added to the form and this value is made
     * the first choice (after the message route.heading).
     */
    private $_firstRouteLabel;
    /**
     * @var AP5L_Forms_Rapid_Globals Reference to global form settings.
     */
    private $_globalSettings;
    /**
     * @var string ID to use for the form.
     */
    private $_id = 'rapidForm';
    /**
     * @var string Prefix to use before id attributes. Allows multiple forms on
     * one page.
     */
    private $_idPrefix = '';
    /**
     * @var boolean Flag set when onInit handler completes and form processing
     * not completed ("done").
     */
    private $_initialized = false;
    /**
     * @var string The language to use for message generation. Defaults to
     * 'ENG'.
     */
    private $_language = 'ENG';
    /**
     * @var string Specifies which version of the form to generate, edit,
     * confirm, done, etc.
     */
    private $_layout;
    /**
     * @var boolean Flag after onLoad event has been triggered..
     */
    private $_loaded = false;
    /**
     * @var array Text of instance-local messages to generate when creating the
     * form.
     */
    private $_messages = array();
    /**
     * @var method External message generation handler. Defaults to none.
     */
    private $_messageHandler = null;
    /**
     * @var string Which preset form to use.
     */
    private $_preset = '';
    /**
     * @var array Array of routes for the form. Each entry is an array of (user
     * text, route address).
     */
    private $_routes = array();
    /**
     * @var boolean When set, generate a raw form without controls required for
     * AP5L_Forms_Rapid processing.
     */
    private $_standAlone = false;
    /**
     * @var object User verifier object. Optional.
     */
    private $_verifier = null;
    /**
     * @var object Reference to the view
     */
    private $_view;
    /**
     * @var string Name of the view class
     */
    private $_viewClass = 'AP5L_Forms_Rapid_View_HtmlTable';
    /**
     * @var string What to put in the form's action attribute. An empty string
     * works in most cases.
     */
    var $action = '';
    /**
     * @var array A stack of control names for identifier generation
     */
    protected $controlPath;

    function __construct($verifier = null, $opts = null) {
        $this -> _globalSettings = AP5L_Forms_Rapid_Globals::singleton();
        if (is_array($verifier)) {
            $this -> _setOptions($verifier);
        } else {
            $this -> _verifier = $verifier;
            if (is_array($opts)) {
                $this -> _setOptions($opts);
            }
        }
        $this -> _layout = 'input';
        $this -> _init();
    }

    function _init() {
        $this -> _view = &new $this -> _viewClass;
        $this -> _fieldContainer = AP5L_Forms_Field::factory('_root', 'root');
        // Create the fields that are needed to implement the presets.
        //            first   last    email   comment
        // capture:     O       O       O       N
        // comment:     O       O       O       R
        // service:     Y       Y       Y       Y
        // subscribe:   Y       Y       Y       Y
        //
        // First name
        $newField = &AP5L_Forms_Field::factory('text', 'firstname', 30, 30);
        $newField -> setStyleRules('*=it service,subscribe=rp capture,comment=vp');
        $this -> _fieldContainer -> addField($newField);
        // Last name
        $newField = &AP5L_Forms_Field::factory('text', 'lastname', 30, 30);
        $newField -> setStyleRules('*=it service,subscribe=rp capture,comment=vp');
        $this -> _fieldContainer -> addField($newField);
        // eMail
        $newField = &AP5L_Forms_Field::factory('email', 'email', 255, 60);
        $newField -> setStyleRules('*=it capture,service,subscribe=rp comment=vp');
        $this -> _fieldContainer -> addField($newField);
        // Comment
        $newField = &AP5L_Forms_Field::factory('textarea', 'comment', 2048,
            array(50, 10), 1000);
        $newField -> setStyleRules('*=it capture,subscribe=ip comment,service=rp');
        $this -> _fieldContainer -> addField($newField);
        // Verifier
        if ($this -> _verifier) {
            $newField = &AP5L_Forms_Field::factory('verifier', 'verifier', 5, 10, 2000);
            $newField -> setStyleRules('*=rt');
            $this -> _fieldContainer -> addField($newField);
        }
    }

    function _save() {
        try {
            $result = $this -> onPrepareData();
            if ($result) {
                $this -> onSave();
            }
            $this -> _error = false;
            $this -> _layout = 'done';
            $this -> _initialized = false;
        } catch (AP5L_Forms_Exception $e) {
            echo $e;
            $this -> _error = $e;
            $this -> _layout = 'error';
        }
    }

    function _setOptions($options) {
        foreach ($options as $option => $value) {
            switch ($option) {
                case 'confirm': {
                    $this -> _confirmStep = $value;
                }
                break;
                case 'debug': {
                    $this -> _debug = $value;
                }
                break;
                case 'verifier': {
                    $this -> _verifier = $value;
                }
                break;
                case 'view': {
                    $this -> _viewClass = $value;
                }
                break;
            }
        }
    }

    function addField(&$field, $initValue = null) {
        $this -> _fieldContainer -> addField($field, $initValue);
    }
    /**
     * Add a routing option to the form.
     * @param string The text to display in a selection box.
     * @param string The destinatin route.
     */
    function addRoute($label, $addr) {
        $slot = count($this -> _routes);
        // We only create a select field if there's more than one choice
        if ($slot) {
            // We have choices. If there's no field, add it.
            try {
                $route = &$this -> _fieldContainer -> getField('route');
            } catch (AP5L_Forms_Exception $e) {
                if ($e -> getCode() != AP5L_Forms_Exception::ERR_NO_FIELD) {
                    throw $e;
                }
                $route = &AP5L_Forms_Field::factory('select', 'route', 40, 80, false, 50);
                $route -> setStyleRules('*=r');
                // Add the equivalent of "select one"
                $route -> addChoice('', '*routepick');
                // Add the saved first choice
                $route -> addChoice(0, $this -> _firstRouteLabel);
                // Add the route to the form
                $this -> _fieldContainer -> addField($route);
                $this -> _messages['route.heading'] = 'Send to';
                $this -> _messages['routepick'] = 'Select One';
            }
            $route -> addChoice($slot, $label);
        } else {
            $this -> _firstRouteLabel = $label;
        }
        $this -> _routes[$slot] = $addr;
    }

    function clear() {
        $fields = $this -> _fieldContainer -> getFields();
        foreach ($fields as $key => $field) {
            $fields[$key] -> clear();
        }
    }

    function generate($echoOut = true) {
        if (! $this -> _loaded) {
            // New form, load runtime stuff
            $this -> onLoad();
            $this -> _loaded = true;
        }
        if (! $this -> _initialized) {
            // New form or previous form completed; initialize
            $this -> onInit();
            $this -> _captchaPassed = false;
            $this -> _initialized = true;
        }
        $this -> _fieldContainer -> arrange($this -> _preset);
        $html = '';
        switch ($this -> _layout) {
            case 'confirm': {
                $html .= $this -> _view -> generateConfirm($this);
            } break;

            case 'error': {
                $html .= $this -> onError();
            } break;

            case 'done': {
                $html .= $this -> onDone();
            } break;

            case 'input': {
                $html .= $this -> _view -> generateEditable($this);
            } break;

            default: {
                throw new AP5L_Forms_Exception('Unknown layout: "' . $this -> _layout . '"');
            } break;
        }
        if ($echoOut) {
            echo $html;
        } else {
            return $html;
        }
    }

    function captchaPassed() {
        return $this -> _captchaPassed;
    }

    function &getContainer() {
        return $this -> _fieldContainer;
    }

    function getDebug() {
        return $this -> _debug;
    }

    function &getField($name) {
        return $this -> _fieldContainer -> getField($name);
    }

    function getId() {
        return $this -> _id;
    }

    function getIdPrefix() {
        return $this -> _idPrefix;
    }

    function getImage() {
        $this -> _verifier -> generate();
    }

    function getMessage($messageID) {
        if (is_null($this -> _messageHandler)) {
            if (isset($this -> _messages[$messageID])) {
                return $this -> _messages[$messageID];
            } else {
                $globals = &AP5L_Forms_Rapid_Globals::singleton();
                return $globals -> getMessage($messageID, $this -> _language);
            }
        } else {
            return call_user_func($this -> _messageHandler, $messageID, $this -> _language);
        }
    }

    function getResults($withTemps = false) {
        return $this -> _fieldContainer -> getResults($withTemps);
    }

    function getRoute() {
        if (count($this -> _routes) == 1) {
            return $this -> _routes[0];
        }
        $route = &$this -> _fieldContainer -> getField('route');
        return $this -> _routes[$route -> getValue()];
    }

    function getStandAlone() {
        return $this -> _standAlone;
    }

    function &getVerifier() {
        return $this -> _verifier;
    }

    /**
     * Get a reference to the current view object
     */
    function &getView() {
        return $this -> _view;
    }

    function idPath($name, $index = '') {
        $pathBits = $this -> controlPath;
        // Consume an empty ID prefix.
        if ($pathBits[0] == '') {
            array_shift($pathBits);
        }
        $path = implode(':', $pathBits);
        return ($path ? $path . ':' : '') . ($index === '' ? $name : $index);
    }

    function isDone() {
        return $this -> _layout == 'done';
    }

    /**
     * Custom action handler. Called when the form action isn't recognized
     * (recognized values are '', 'back', 'check' and 'save').
     */
    function onAction($action) {
        return '';
    }

    /**
     * Form processing completed event handler.
     * @return string Test to show the user when processing is done.
     */
    function onDone() {
        // nothing to do handler -- to be overridden
        return '';
    }

    function onError() {
        // error handler -- to be overridden
        return $this -> getMessage('save_error');
    }

    /**
     * Form submission handler. Handles submit buttons with custom values.
     * Return an action string.
     * @return string The identifier for the action to be taken.
     */
    function onFormSubmit() {
        return '';
    }

    /**
     * Form initialization handler. Default is to clear all fields.
     */
    function onInit() {
        //
        // No form posted; clear everything out
        //
        $this -> clear();
        //
        // Force reset of flags for visibility, etc.
        //
        $this -> _fieldContainer -> setSorted(false);
        return true;
    }

    /**
     * Initial load event. This is triggered once per form creation, before the
     * first call to init.
     */
    function onLoad() {
        return true;
    }

    /**
     * Prepare data for save operation.
     *
     * Return true if the save should proceed, false if no save and no error,
     * throw exception on error.
     */
    function onPrepareData() {
        return true;
    }

    /**
     * Save event handler. Overide to send mail, save in database, etc.
     */
    function onSave() {
        // Function should be overridden
        throw new AP5L_Forms_Exception('Data not saved, override onSave() method!');
    }

    /**
     * Create a standard form pre-populated for a preset purpose.
     *
     * Preset form styles include "capture"; "comment" -- a comment form that
     * allows anonymous comments; "service" -- a customer service request, where
     * contact information is required; "subscribe"
     */
    function preset($style, $title = '') {
        $this -> _preset = $style;
        switch ($style) {
            case 'capture': {
                $autoTitle = '';
            } break;

            case 'comment': {
                $autoTitle = 'Comment Form (No Response Required)';
            } break;

            case 'service': {
                $autoTitle = 'Customer Service Request Form';
            } break;

            case 'subscribe': {
                $autoTitle = 'Subscription Form';
            } break;

            default: {
                return false;
            }
        }
        $this -> _fieldContainer -> setSorted(false);
        if ($title == '') {
            $title = $autoTitle;
        }
        $this -> _messages['title'] = $title;
        return true;
    }

    function process() {
        //echo 'POST:<pre>' . htmlentities(print_r($_POST, true)) . '</pre>';
        //echo 'The answer is ' . $this -> _verifier -> getAnswer() . '<br/>';
        $this -> _layout = 'input';
        $fields = &$this -> _fieldContainer -> getFields();
        if (isset($_POST['formID']) && $_POST['formID'] == 'AP5L_Forms_Rapid') {
            if (isset($_POST['buttonBack']) && $_POST['buttonBack'] == 'click_back') {
                $action = 'back';
            } elseif (isset($_POST['buttonCheck']) && $_POST['buttonCheck'] == 'click_check') {
                $action = 'check';
            } elseif (isset($_POST['buttonSave']) && $_POST['buttonSave'] == 'click_save') {
                $action = 'save';
            } else {
                $action = $this -> onFormSubmit();
            }
            switch ($action) {
                case '': {
                    // Explicit do-nothing
                }
                break;

                case 'back': {
                    // nothing to do
                }
                break;

                case 'check': {
                    $isValid = $this -> processUserData($fields);
                    if ($isValid) {
                        if ($this -> _confirmStep) {
                            $this -> _layout = 'confirm';
                        } else {
                            $this -> _save();
                        }
                    }
                }
                break;

                case 'save': {
                    $this -> _save();
                }
                break;

                default: {
                    $this -> onAction($action);
                }
            }
        }
    }

    function processField(&$field, &$value, $options = null) {
        if (! $field -> isVisible()) {
            return true;
        }
        if ($this -> _debug) {
            echo $field -> getName() . ': ' . $value . '<br/>';
        }
        switch (get_class($field)) {
            case 'AP5L_Forms_Field_ScalarArray': {
                return $this -> processFieldScalarArray($field, $value, $options);
            }
            break;
            case 'AP5L_Forms_Field_Scalar': {
                return $this -> processFieldScalar($field, $value, $options);
            }
            break;
            default: {
                throw new AP5L_Forms_Exception('no process field handler for ' . get_class($field));
            }
            break;
        }
    }

    function processFieldScalar(&$field, &$value, $options = null) {
        if ($field -> isStatic()) {
            return true;
        }
        $nameIndex = isset($options['index']) ? $options['index'] : '';
        $coreName = $field -> getName(0);
        $id = $this -> idPath($field -> getName(0), $nameIndex);
        if (isset($_POST[$id])) {
            $formData = $_POST[$id];
            if (is_string($formData)) {
                $formData = stripslashes($formData);
            }
        } else {
            $formData = '';
        }
        $fieldError = false;
        $fieldValid = true;
        switch ($field -> getType()) {
            case 'check': {
            }
            break;

            case 'email': {
                if ($field -> isRequired()) {
                    if ($formData == '') {
                        $fieldValid = false;
                    } else if (! eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,6})$", $formData)) {
                        $fieldError = true;
                    }
                }
            }
            break;

            case 'label': {
                // Nothing to do
            }
            break;

            case 'pass': {
                if ($field -> isRequired() && ($formData == '')) {
                    $fieldValid = false;
                }
            }
            break;

            case 'radio': {
            }
            break;

            case 'setpass': {
                if (! $field -> isRequired() && ($formData == '')) {
                    // Not required and no data, that's okay.
                    $fieldValid = true;
                } else if ($formData != $_POST[$this -> idPath($field -> getName(-1), $nameIndex)]) {
                    // Mismatched verification
                    $fieldError = true;
                } else {
                    // values match; check size criteria.
                    $size = strlen($formData);
                    $dSize = $field -> getDisplaySize();
                    if (is_array($dSize)) {
                        $fieldError = $size < $dSize[0]
                            || $size > $dSize[1];
                    } else {
                        $fieldError = ($size != $dSize);
                    }
                }
            }
            break;

            case 'select': {
                if ($field -> isRequired() && ($formData == '')) {
                    $fieldValid = false;
                }
            }
            break;

            case 'text': {
                if ($field -> isRequired() && ($formData == '')) {
                    $fieldValid = false;
                }
            }
            break;

            case 'textarea': {
                if ($field -> isRequired() && ($formData == '')) {
                    $fieldValid = false;
                }
            }
            break;

            case 'verifier': {
                if (! $this -> _captchaPassed) {
                    if ($formData == '') {
                        $fieldValid = false;
                    } else if (strtolower($this -> _verifier -> getAnswer()) !=
                        strtolower($formData)) {
                        $fieldError = true;
                    }
                    if ($fieldValid && ! $fieldError) {
                        //
                        // Once the user has proven that they're not a bot,
                        // there's no need to repeat the test on subsequent
                        // passes just because some other data is wrong.
                        //
                        $this -> _captchaPassed = true;
                    }
                }
                $formData = '';
            }
            break;

        }
        $field -> isError = $fieldError;
        if ($fieldError) {
            $fieldValid = false;
        }
        $field -> isValid = $fieldValid;
        $value = $formData;
        return $fieldValid;
    }

    function processFieldScalarArray(&$field, &$value, $options = null) {
        $nameIndex = isset($options['index']) ? $options['index'] : '';
        $coreName = $field -> getName(0);
        $id = $this -> idPath($coreName, $nameIndex);
        $this -> controlPath[] = $coreName;
        if ($nameIndex != '') {
            $this -> controlPath[] = $nameIndex;
        }
        $isValid = true;
        $fields = $field -> getFields();
        switch ($field -> getType()) {
            case 'cols':
            case 'rows': {
                //  Process row/cloumn data
                $valueMatrix = &$field -> getValueRef();
                for ($row = 0; $row < $field -> getElementCount(); ++$row) {
                    $colIndx = 0;
                    foreach ($fields as $key => $col) {
                        $this -> controlPath[] = $col -> getName();
                        if (! $this -> processField($fields[$key],
                            $valueMatrix[$colIndx++][$row],
                            array('index' => $row))
                        ) {
                            $isValid = false;
                        }
                        array_pop($this -> controlPath);
                    }
                }
            }
            break;
        }
        if ($nameIndex != '') {
            array_pop($this -> controlPath);
        }
        array_pop($this -> controlPath);
        return $isValid;
    }

    function processUserData($fields) {
        //
        // Validate all visible fields
        //
        if ($this -> _debug) {
            echo 'processUserData POST:<pre>' . print_r($_POST, true) . '</pre>';
        }
        $this -> controlPath = array($this -> _idPrefix);
        $isValid = true;
        foreach ($fields as $key => $field) {
            if (! $this -> processField($fields[$key], $fields[$key] -> getValueRef())) {
                $isValid = false;
            }
        }
        return $isValid;
    }

    function setDebug($debug) {
        $this -> _debug = $debug;
    }

    static function setClassMessage($id, $text) {
        $globals = AP5L_Forms_Rapid_Globals::singleton();
        $globals -> setMessage($id, $this -> _language, $text);
    }

    function setId($id) {
        $this -> _id = $id;
    }

    function setIdPrefix($prefix) {
        $this -> _idPrefix = $prefix;
    }

    function setMessage($id, $text) {
        $this -> _messages[$id] = $text;
    }

    function setMessageHandler($handler = null) {
        $this -> _messageHandler = $handler;
    }

    function setStandAlone($standAlone) {
        $this -> _standAlone = $standAlone;
    }

    function setView(&$view) {
        $this -> _view = $view;
    }

    function validate() {
    }

}

?>