<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2006-2008, Alan Langford
 * @version $Id: HtmlTable.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Table based HTML view for forms.
 * 
 * @package AP5L
 * @subpackage Forms
 */
class AP5L_Forms_Rapid_View_HtmlTable extends AP5L_Forms_Rapid_View {
    /**
     * Text to be generated before an error message.
     * 
     * @var string 
     */
    private $_errMsgPre = '';
    /**
     * Text to be generated after an error message.
     * 
     * @var string
     */
    private $_errMsgPost = '';
    /**
     * Text/HTML to generate before the <form>.
     * @var string 
     */
    private $_htmlPost = '';
    /**
     * Text/HTML to generate after the <form>.
     * @var string 
     */
    private $_htmlPre = '';
    /**
     * URL of the script to use to generate a captcha image. Optional. If
     * absent, the verifier's embed() method is called.
     * 
     * @var string 
     */
    private $_imagePath;
    /**
     * Text/HTML to generate after a required field label. Appears inside the
     * required style span.
     * 
     * @var string 
     */
    private $_requiredPost;
    /**
     * Text/HTML to generate before a required field label. Appears inside the
     * required style span.
     * 
     * @var string 
     */
    private $_requiredPre;
    /**
     * Prefix to use for styles used by the form. Default is 'cform_'.
     * 
     * @var string 
     */
    private $_stylePrefix = 'cform_';
    /**
     * Code to generate after a tip.
     * 
     * @var string 
     */
    private $_tipPost;
    /**
     * Code to generate before a tip.
     * 
     * @var string 
     */
    private $_tipPre;
    /**
     * When set, generate CSS style references. Default is true.
     * 
     * @var boolean
     */
    private $_useStyles = true;

    function _fieldConfirm(&$form, &$field, $value, $options = null) {
        switch (get_class($field)) {
            case 'AP5L_Forms_Field_Scalar': {
                return $this -> _fieldConfirmScalar($form, $field, $value, $options);
            }
            break;
            case 'AP5L_Forms_Field_ScalarArray': {
                return $this -> _fieldConfirmScalarArray($form, $field, $value, $options);
            }
            break;
            default: {
                return 'no field confirm handler for ' . get_class($field, $options);
            }
            break;
        }
    }

    function _fieldConfirmScalar(&$form, &$field, $value, $options) {
        $nameIndex = isset($options['index']) ? $options['index'] : '';
        $html = '';
        switch ($field -> getType()) {
            case 'button': {
                $html .= $this -> _generateButton($field, $value, $nameIndex);
            }
            break;

            case 'check': {
                if (count($field -> getChoices())) {
                    $delim = '';
                    foreach ($field -> getChoices() as $key => $choice) {
                        $html .= $delim;
                        if (is_array($field ->  getValue())) {
                            if (in_array($choice['value'], $field ->  getValue())) {
                                $html .= $form -> getMessage('check.checked');
                            } else {
                                $html .= $form -> getMessage('check.unchecked');
                            }
                        } else {
                            if ($choice['value'] == $field ->  getValue()) {
                                $html .= $form -> getMessage('check.checked');
                            } else {
                                $html .= $form -> getMessage('check.unchecked');
                            }
                        }
                        $html .= ' ';
                        if (substr($choice['label'], 0, 1) == '*') {
                            $html .= $form -> getMessage(substr($choice['label'], 1));
                        } else {
                            $html .= htmlentities($choice['label']);
                        }
                        $delim = '<br/>';
                    }
                } else {
                    if ($value) {
                        $html .= $form -> getMessage('check.checked');
                    } else {
                        $html .= $form -> getMessage('check.unchecked');
                    }
                    $html .= $form -> getMessage($field -> getName() . '.label');
                }
            } break;

            case 'email':
            case 'text': {
                $html .= $value ? $value : $form -> getMessage('text.empty');
            } break;

            case 'header':
            case 'label': {
                $html .= $value;
            } break;

            case 'hidden': {
                // No output
            }
            break;

            case 'pass': {
                $html .= str_pad('', $field -> getDisplaySize(), '*');
            } break;

            case 'radio': {
                $noValue = true;
                foreach ($field -> getChoices() as $choice) {
                    if ($choice['value'] == $field ->  getValue()) {
                        $noValue = false;
                        if (substr($choice['label'], 0, 1) == '*') {
                            $html .= $form -> getMessage(substr($choice['label'], 1));
                        } else {
                            $html .= $choice['label'];
                        }
                    }
                }
                if ($noValue) {
                    $html .= $form -> getMessage('text.empty');
                }
            } break;

            case 'select': {
                foreach ($field -> getChoices() as $choice) {
                    if ($choice['value'] == $field ->  getValue()) {
                        if (substr($choice['label'], 0, 1) == '*') {
                            $html .= $form -> getMessage(substr($choice['label'], 1));
                        } else {
                            $html .= $choice['label'];
                        }
                    }
                }
            } break;

            case 'setpass': {
                // Generate a masked indicator of the password
                if (is_array($field -> getDisplaySize())) {
                    $size = $field -> getDisplaySize();
                    $size = $size[1];
                } else {
                    $size = $field -> getDisplaySize();
                }
                $html .= str_pad('', $size, '*');
            } break;

            case 'submit': {
                $html .= $this -> _generateSubmit($field, $value, $nameIndex);
            }
            break;
            case 'textarea': {
                $html .= htmlentities($value);
            } break;

            case 'verifier': {
            } break;

            default: {
                $html .= 'unimplemented type ' . $field -> getType();
            } break;
        }
        return $html;
    }

    function _fieldConfirmScalarArray(&$form, &$field, $value, $options) {
        // Generate a containing table with sufficient rows/columns
        $nameIndex = isset($options['index']) ? $options['index'] : '';
        $coreName = $field -> getName(0);
        $id = $this -> idPath($field -> getName(0), $nameIndex);
        $name = ' name="' . $id . '"';
        $idHtml = ' id="' . $id . '"';
        $html = '';
        $fields = $field -> getFields();
        $this -> controlPath[] = $coreName;
        if ($nameIndex != '') {
            $this -> controlPath[] = $nameIndex;
        }
        switch ($field -> getType()) {
            case 'cols': {
                /*-----------------------------
                $html .= '<colgroup><col' . $this -> _hc('headcol') . '/>'
                    . '<col' . $this -> _hc('contentcol') . '/>'
                    . '</colgroup>';
                -----------------------------*/
                // cols:
                //  generate header; all values
                //  continue through rows.
                $html .= 'cols unimplemented';
            }
            break;
            case 'rows': {
                $anyHeader = false;
                $anyVisible = false;
                foreach ($fields as $col) {
                    if ($col -> isVisible()) {
                        $anyVisible = true;
                        if ($col -> getLabelMode() >= 0) {
                            $anyHeader = true;
                        }
                    }
                }
                if ($anyVisible) {
                    $html .= '<table' . $idHtml . $this -> _hc('subtab') . $name . '>';
                }
                //  Generate headers as required
                if ($anyHeader) {
                    $html .= '<tr>';
                    foreach ($fields as $col) {
                        if ($col -> isVisible()) {
                            $html .= '<th'
                                . $this -> _hc(
                                    'thc',
                                    ($col -> labelClass === false ? $field -> labelClass : $col -> labelClass)
                                ) . '>';
                            if ($col -> getLabelMode() == 1) {
                                // Heading with label
                                $html .= $form -> getMessage($col -> getHeadingName() . '.heading')
                                    . '</th>';
                            } else {
                                // Empty heading
                                $html .= '</th>';
                            }
                        }
                    }
                    $html .= '</tr>' . chr(10);
                }
                //  pump out rows
                if ($anyVisible) {
                    $valueMatrix = $field -> getValueRef();
                    for ($row = 0; $row < $field -> getElementCount(); ++$row) {
                        $html .= '<tr>';
                        foreach ($fields as $col) {
                            if ($col -> isVisible()) {
                                $this -> controlPath[] = $col -> getName();
                                $html .= '<td'
                                    . $this -> _hc(
                                        'td',
                                        ($col -> getClass() === false ? $field -> getClass() : $col -> getClass()),
                                        $row
                                    ) . '>'
                                    . $this -> _fieldConfirm(
                                        $form, $col,
                                        $valueMatrix[$col -> valueIndex][$row],
                                        array('index' => $row)
                                    )
                                    . '</td>';
                                array_pop($this -> controlPath);
                            }
                        }
                        $html .= '</tr>' . chr(10);
                    }
                    $html .= '</table>';
                }
            }
            break;
        }
        if ($nameIndex != '') {
            array_pop($this -> controlPath);
        }
        array_pop($this -> controlPath);
        return $html;
    }

    function _fieldEditable(&$form, &$field, $value, $options = null) {
        switch (get_class($field)) {
            case 'AP5L_Forms_Field_Scalar': {
                return $this -> _fieldEditableScalar($form, $field, $value, $options);
            }
            break;
            case 'AP5L_Forms_Field_ScalarArray': {
                return $this -> _fieldEditableScalarArray($form, $field, $value, $options);
            }
            break;
            default: {
                return 'no field editable handler for ' . get_class($field);
            }
            break;
        }
    }

    function _fieldEditableScalar(&$form, &$field, $value, $options) {
        $phase = isset($options['phase']) ? $options['phase'] : 0;
        $nameIndex = isset($options['index']) ? $options['index'] : '';
        $id = $this -> idPath($field -> getName($phase), $nameIndex);
        $name = ' name="' . $id . '"';
        $idHtml = ' id="' . $id . '"';
        $html = '';
        switch ($field -> getType()) {
            case 'button': {
                $html .= $this -> _generateButton($field, $value, $nameIndex);
            }
            break;

            case 'check': {
                if (count($field -> getChoices())) {
                    $delim = '';
                    foreach ($field -> getChoices() as $key => $choice) {
                        $subName = $field -> getName($phase) . '[' . $key . ']';
                        $html .= $delim . '<label' . $this -> _hc('label') . '>'
                            . '<input' . $this -> _hc('check')
                            . ' type="checkbox"'
                            . ' name="' . $subName . '"'
                            . ' id="' . $id . '_' . $key . '"'
                            . ' value="' . $choice['value'] . '"';
                        if (is_array($field ->  getValue())) {
                            if (in_array($choice['value'], $value)) {
                                $html .= ' checked';
                            }
                        } else {
                            if ($choice['value'] == $value) {
                                $html .= ' checked';
                            }
                        }
                        $html .= ' />';
                        if (substr($choice['label'], 0, 1) == '*') {
                            $html .= $form -> getMessage(substr($choice['label'], 1));
                        } else {
                            $html .= htmlentities($choice['label']);
                        }
                        $html .= '</label>';
                        $delim = '<br/>';
                    }
                } else {
                    $html .= '<label' . $this -> _hc('label') . '>'
                        . '<input' . $this -> _hc('check') . ' type="checkbox"'
                        . $name . $idHtml
                        . ' value="1"' . ($value ? 'checked ' : '') . '/>'
                        . $form -> getMessage($field -> getName() . '.label')
                        . '</label>';
                }
            } break;

            case 'email':
            case 'text': {
                $html .= '<input type="text"' . $this -> _hc('text')
                    . $name . $idHtml
                    . ' value="' . htmlentities($value) . '"'
                    . ' size="' . $field -> getDisplaySize() . '"'
                    . ' maxlength="' . $field -> getMaxLength() . '"/>';
            } break;

            case 'header':
            case 'label': {
                $html .= '<span ' . $idHtml . '>'
                    . htmlentities($value) . '</span>';
            } break;

            case 'hidden': {
                $html .= '<input type="hidden" name="' . $field -> getName() . '"'
                    . $idHtml . 'value="' . $value . '"/>';
            }
            break;

            case 'pass': {
                $html .= '<input type="password"' . $this -> _hc('pass')
                    . $name . $idHtml
                    . 'value="' . htmlentities($value) . '"'
                    . ' size="' . $field -> getDisplaySize() . '"'
                    . ' maxlength="' . $field -> getMaxLength() . '"/>';
            } break;

            case 'radio': {
                $delim = '';
                foreach ($field -> getChoices() as $key => $choice) {
                    $html .= $delim . '<label' . $this -> _hc('label') . '>'
                        . '<input type="radio"' . $this -> _hc('radio')
                        . $name . ' id="' . $id . '_' . $key . '"'
                        . ' value="' . $choice['value'] . '"';
                    if ($choice['value'] == $value) {
                        $html .= ' checked';
                    }
                    $html .= ' />';
                    if (substr($choice['label'], 0, 1) == '*') {
                        $html .= $form -> getMessage(substr($choice['label'], 1));
                    } else {
                        $html .= htmlentities($choice['label']);
                    }
                    $html .= '</label>';
                    $delim = '<br/>';
                }
            } break;

            case 'select': {
                $html .= '<select' . $this -> _hc('sel') . $name . $idHtml . '>';
                foreach ($field -> getChoices() as $choice) {
                    $html .= '<option' . $this -> _hc('opt')
                        . ' value="' . $choice['value'] . '"';
                    if ($choice['value'] == $value) {
                        $html .= ' selected="selected"';
                    }
                    $html .= '>';
                    if (substr($choice['label'], 0, 1) == '*') {
                        $html .= $form -> getMessage(substr($choice['label'], 1));
                    } else {
                        $html .= htmlentities($choice['label']);
                    }
                    $html .= '</option>';
                }
                $html .= '</select>';
            } break;

            case 'setpass': {
                if (is_array($field -> getDisplaySize())) {
                    $size = $field -> getDisplaySize();
                    $size = $size[1];
                } else {
                    $size = $field -> getDisplaySize();
                }
                $html .= '<input type="password"' . $this -> _hc('pass')
                    . $name . $idHtml . ' size="' . $size . '"'
                    . ' maxlength="' . $field -> getMaxLength() . '" value=""/>';
            } break;

            case 'submit': {
                $html .= $this -> _generateSubmit($field, $value, $nameIndex);
            }
            break;

            case 'textarea': {
                // Define some default events to limit char size to maxlength
                $clip = '"this.value=this.value.substring(0,'
                    . $field -> getMaxLength() . ')"';
                $events = array('onchange' => $clip, 'onkeyup' => $clip);
                // Merge in application provided events
                foreach ($field -> getEvents() as $fevent => $fdata) {
                    $events[$fevent] = '"' . addcslashes($fdata, '"') . '"';
                }
                $size = $field -> getDisplaySize();
                $html .= '<textarea' . $idHtml . $name . $this -> _hc('text')
                    . ' cols="' . $size[0] . '"'
                    . ' rows="' . $size[1] . '"';
                foreach ($events as $event => $data) {
                    $html .= ' ' . $event . '=' . $data;
                }
                $html .=  '>' . htmlentities($value) . '</textarea>';
            } break;

            case 'verifier': {
                $form -> getVerifier() -> create($field -> getMaxLength());
                if ($this -> _imagePath) {
                    $image = $this -> _imagePath;
                    if (strpos($image, '?') === false) {
                        $image .= '?';
                    } else {
                        $image .= '&amp;';
                    }
                    $image .= 'id=' . $form -> getId();
                } else {
                    $image = $form -> getVerifier() -> embed();
                }
                $html .= '<img'  . $this -> _hc('image')
                    . ' src="' . $image . '"'
                    .' alt="Bot Blocker Image"/><br/>'
                    . '<input type="text"' . $this -> _hc('text')
                    . $name . $idHtml . ' value=""'
                    . ' size="' . $field -> getDisplaySize() . '"'
                    . ' maxlength="' . $field -> getMaxLength() . '"/>';
            } break;

            default: {
                $html .= 'unimplemented type ' . $field -> getType();
            } break;
        }
        if ($phase == 0) {
            if ($field -> helpText) {
                $html .= $this -> _tipPre . $field -> helpText . $this -> _tipPost;
            }
            if (! $field -> isValid) {
                $html .= $this -> _errMsgPre . '<span' . $this -> _hc('errmsg') . '>';
                if ($field -> isError) {
                    $html .= $form -> getMessage($field -> getName($phase) . '.bad');
                } else {
                    $html .= $form -> getMessage('required.bad');
                }
                $html .=  '</span>' . $this -> _errMsgPost;
            }
        }
        return $html;
    }

    function _fieldEditableScalarArray(&$form, &$field, $value, $options) {
        // Generate a containing table with sufficient rows/columns
        $phase = isset($options['phase']) ? $options['phase'] : 0;
        $nameIndex = isset($options['index']) ? $options['index'] : '';
        $coreName = $field -> getName(0);
        $id = $this -> idPath($field -> getName(0), $nameIndex);
        $name = ' name="' . $this -> idPath($field -> getName($phase), $nameIndex) . '"';
        $idHtml = ' id="' . $id . '"';
        $html = '';
        $fields = $field -> getFields();
        $this -> controlPath[] = $coreName;
        if ($nameIndex != '') {
            $this -> controlPath[] = $nameIndex;
        }
        switch ($field -> getType()) {
            case 'cols': {
                /*-----------------------------
                $html .= '<colgroup><col' . $this -> _hc('headcol') . '/>'
                    . '<col' . $this -> _hc('contentcol') . '/>'
                    . '</colgroup>';
                -----------------------------*/
                // cols:
                //  generate header; all values
                //  continue through rows.
                $html .= 'cols unimplemented';
            }
            break;
            case 'rows': {
                //  Generate headers as required
                $anyHeader = false;
                $anyVisible = false;
                foreach ($fields as $col) {
                    if ($col -> isVisible()) {
                        $anyVisible = true;
                        if ($col -> getLabelMode() >= 0) {
                            $anyHeader = true;
                        }
                    }
                }
                if ($anyVisible) {
                    $html .= '<table' . $idHtml . $this -> _hc('subtab') . $name . '>';
                }
                if ($anyHeader) {
                    $html .= '<tr>';
                    foreach ($fields as $col) {
                        if ($col -> isVisible()) {
                            $html .= '<th'
                                . $this -> _hc(
                                    'thc',
                                    ($col -> labelClass === false ? $field -> labelClass : $col -> labelClass)
                                ) . '>';
                            if ($col -> getLabelMode() == 1) {
                                // Heading with label
                                $html .= $form -> getMessage($col -> getHeadingName() . '.heading')
                                    . '</th>';
                            }
                            $html .= '</th>';
                        }
                    }
                    $html .= '</tr>' . chr(10);
                }
                //  pump out rows
                $valueMatrix = $field -> getValueRef();
                for ($row = 0; $row < $field -> getElementCount(); ++$row) {
                    // Look for hidden columns
                    $hiddenHtml = '';
                    foreach ($fields as $col) {
                        if (! $col -> isVisible()) {
                            $this -> controlPath[] = $col -> getName();
                            $hiddenHtml .= $this -> _fieldEditable(
                            $form, $col,
                            $valueMatrix[$col -> valueIndex][$row],
                            array('phase' => $phase, 'index' => $row));
                            array_pop($this -> controlPath);
                        }
                    }
                    if ($anyVisible) {
                        $html .= '<tr>';
                        foreach ($fields as $col) {
                            if ($col -> isVisible()) {
                                $this -> controlPath[] = $col -> getName();
                                $html .= '<td'
                                    . $this -> _hc(
                                        'td',
                                        ($col -> getClass() === false ? $field -> getClass() : $col -> getClass()),
                                        $row
                                    ) . '>'
                                    . $hiddenHtml
                                    . $this -> _fieldEditable(
                                        $form, $col,
                                        $valueMatrix[$col -> valueIndex][$row],
                                        array('phase' => $phase, 'index' => $row)
                                    )
                                    . '</td>';
                                $hiddenHtml = '';
                                array_pop($this -> controlPath);
                            }
                        }
                        $html .= '</tr>' . chr(10);
                    } else {
                        $html .= $hiddenHtml;
                    }
                }
                if ($anyVisible) {
                    $html .= '</table>';
                }
            }
            break;
        }
        if ($nameIndex != '') {
            array_pop($this -> controlPath);
        }
        array_pop($this -> controlPath);
        return $html;
    }

    function _generateButton($field, $value, $nameIndex) {
        $attrs = array('value' => '');
        if (! is_array($value)) {
            $label = $value;
        } else {
            $label = '';
            foreach ($value as $key => $data) {
                if (substr($key, 0, 2) == 'on') {
                    $attrs[$key] = $data;
                } elseif ($key == 'text') {
                    $label = $data;
                } elseif ($key == 'value') {
                    $attrs[$key] = $data;
                }
            }
        }
        $html = '<button' . $this -> _hc('button', $field -> getClass(), $nameIndex)
            . ' id="' . $name = $this -> idPath($field -> getName()) . '"'
            . ' name="' . $field -> getName() . '" type="button"';
        foreach ($attrs as $attr => $data) {
            $html .= ' ' . $attr . '="' . $data . '"';
        }
        $html .= '>' . $label . '</button>';
        return $html;
    }

     function _generateConfirmContainer($form, $container) {
        //switch on container type
        $html = '';
        $wrapper = '<div>';
        foreach ($container -> getFields() as $field) {
            if ($field -> getType() == 'hidden') {
                    $html .= $wrapper . '<input type="hidden"'
                        . ' name="' . $field -> getName() . '"'
                        . 'value="' . $field -> getValue() . '"/>';
                    $wrapper = '';
            }
        }
        if (! $wrapper) {
            $html .= '</div>';
        }
        $html .= '<table' . $this -> _hc('tab') . '>'
            . '<colgroup><col' . $this -> _hc('headcol') . '/>'
            . '<col' . $this -> _hc('contentcol') . '/>'
            . '</colgroup>';
        if ($msg = $form -> getMessage('title')) {
            $html .= '<tr><th colspan="2"' . $this -> _hc('title') . '>' . $msg
                . '</th></tr>';
        }
        // Generate "review your stuff, press the <blah> button"
        $msg = $form -> getMessage('confirm.heading');
        $msg2 = $form -> getMessage('confirm.heading2');
        if ($msg || $msg2) {
            $html .= '<tr><td colspan="2"' . $this -> _hc('td') . '>' . $msg
                . $form -> getMessage('button.back') . $msg2 . '</td></tr>';
        }
        // Generate all the fields
        foreach ($container -> getFields() as $field) {
            if (! $field -> isVisible()) continue;
            if (in_array($field -> getType(), array('hidden', 'verifier'))) continue;
            $html .= $this -> _generateConfirmRow($form, $field);
        }
        if (! $form -> getStandAlone()) {
            // Create the button row
            $tCell = '<td' . $this -> _hc('td') . '>';
            $html .= '<tr>' . $tCell . '&nbsp;</td>'
                . $tCell . '<button ' . $this -> _hc('button')
                . 'name="buttonSave" value="" type="submit"'
                . ' onclick="this.style.visibility=\'hidden\';this.value=\'click_save\'">'
                . $form -> getMessage('button.save') .'</button>'
                . '<button ' . $this -> _hc('button')
                . 'name="buttonBack" value="" type="submit"'
                . ' onclick="this.style.visibility=\'hidden\';this.value=\'click_back\'">'
                . $form -> getMessage('button.back') .'</button>';
            $html .= '</td></tr>' . chr(10);
        }
        $html .= '</table>';
        return $html;
    }

    function _generateConfirmRow(&$form, &$field) {
        $hCell = '<th' . $this -> _hc('th', $field -> labelClass) . '>';
        $tCell = '<td' . $this -> _hc('td', $field -> getClass()) . '>';
        $html = '<tr>';
        // Generate the header, if required
        switch ($field -> getLabelMode()) {
            case -1: {
                // No heading in this row, open a two column cell
                $html .= '<td colspan="2"' . $this -> _hc('header') . '>';
            }
            break;
            case 0: {
                // Empty heading, generate place holder and open cell
                $html .= $hCell . '</th>' . $tCell;
            }
            break;
            case 1: {
                // Heading with label, generate label and open cell
                $html .= $hCell
                    . $form -> getMessage($field -> getHeadingName() . '.heading')
                    . '</th>' . $tCell;
            }
            break;
        }
        $html .= $this -> _fieldConfirm($form, $field, $field -> getValue()) . '</td></tr>' . chr(10);
        return $html;
    }

    function _generateEditableContainer($form, $container) {
        $hasRequired = false;
        $html = '';
        // Generate all the hidden fields first
        $wrapper = '<div>';
        foreach ($container -> getFields() as $field) {
            if ($field -> getType() == 'hidden') {
                    $name = $this -> idPath($field -> getName());
                    $html .= $wrapper . '<input type="hidden" name="' . $name . '"'
                        . ' id="' . $name . '"'
                        . ' value="' . $field -> getValue() . '"/>';
                    $wrapper = '';
            }
            if ($field -> isRequired()) {
                $hasRequired = true;
            }
        }
        if (! $wrapper) {
            $html .= '</div>';
        }
        $html .= '<table' . $this -> _hc('tab') . '>'
            . '<colgroup><col' . $this -> _hc('headcol') . '/>'
            . '<col' . $this -> _hc('contentcol') . '/>'
            . '</colgroup>';
        if ($msg = $form -> getMessage('title')) {
            $html .= '<tr><th colspan="2"' . $this -> _hc('title') . '>' . $msg
                . '</th></tr>';
        }
        if ($msg = $form -> getMessage('edit.heading')) {
            $html .= '<tr><th colspan="2"' . $this -> _hc('td') . '>' . $msg
                . '</th></tr>';
        }
        foreach ($container -> getFields() as $field) {
            if (! $field -> isVisible()) continue;
            if ($field -> getType() == 'hidden') continue;
            $html .= $this -> _generateEditableRow($form, $field, array('phase' => -1));
            $html .= $this -> _generateEditableRow($form, $field, array('phase' => 0));
            $html .= $this -> _generateEditableRow($form, $field, array('phase' => 1));
        }
        $tCell = '<td' . $this -> _hc('td') . '>';
        if ($hasRequired) {
            $html .= '<tr>' . $tCell . '&nbsp;</td>' . $tCell
                . '<span' . $this -> _hc('req') . '>'
                . $this -> _requiredPre . $form -> getMessage('required')
                . $this -> _requiredPost . '</span>'
                . '</td></tr>';
        }
        if (! $form -> getStandAlone()) {
            $html .= '<tr><td' .$this -> _hc('td') . '>&nbsp;</td>'
                . $tCell . '<button ' . $this -> _hc('button')
                . 'name="buttonCheck" value="" type="submit"'
                . ' onclick="this.style.visibility=\'hidden\';this.value=\'click_check\'">'
                . $form -> getMessage('button.check') . '</button>';
            $html .= '</td></tr>' . chr(10);
        }
        $html .= '</table>';
        return $html;
    }

    function _generateEditableRow(&$form, &$field, $options) {
        $phase = isset($options['phase']) ? $options['phase'] : 0;
        $nameIndex = isset($options['index']) ? $options['index'] : '';
        if (! $field -> hasPhase($phase)) return;
        $fieldType = $field -> getType();
        if ($fieldType == 'verifier' && $form -> captchaPassed()) return;
        $hCell = '<th' . $this -> _hc('th', $field -> labelClass) . '>';
        $tCell = '<td' . $this -> _hc('td', $field -> getClass()) . '>';
        $html = '<tr>';
        // Generate the header, if required
        switch ($field -> getLabelMode()) {
            case -1: {
                // No heading in this row, open a two column cell
                $html .= '<td colspan="2"' . $this -> _hc('header') . '>';
            }
            break;
            case 0: {
                // Empty heading, generate place holder and open cell
                $html .= $hCell . '</th>' . $tCell;
            }
            break;
            case 1: {
                // Heading with label, generate label and open cell
                $html .= $hCell;
                // Generate the label so it's associated with the control group
                if (! in_array($fieldType, array('check', 'radio'))) {
                    $html .= '<label' . $this -> _hc('label') . ' for="'
                        . $this -> idPath($field -> getName($phase), $nameIndex) . '">';
                    $endLabel = '</label>';
                } else {
                    $endLabel = '';
                }
                $msg = $form -> getMessage($field -> getHeadingName($phase) . '.heading');
                if ($msg && $field -> isRequired()) {
                    $html .= '<span' . $this -> _hc('req') . '>'
                    . $this -> _requiredPre . $msg . $this -> _requiredPost . '</span>';
                } else {
                    $html .= $msg;
                }
                $html .= $endLabel . '</th>' . $tCell;
            }
            break;
        }
        $html .= $this -> _fieldEditable($form, $field, $field -> getValue(), $options);
        $html .= '</td></tr>' . chr(10);
        return $html;
    }

    function _generateSubmit($field, $value, $nameIndex) {
        $attrs = array('onclick' => 'this.style.visibility=\'hidden\';');
        if (! is_array($value)) {
            $label = $value;
        } else {
            $label = '';
            foreach ($value as $key => $data) {
                if ($key == 'onclick') {
                    $attrs[$key] .= $data . ';';
                } elseif (substr($key, 0, 2) == 'on') {
                    $attrs[$key] = $data;
                } elseif ($key == 'text') {
                    $label = $data;
                } elseif ($key == 'value') {
                    $attrs['onclick'] .= 'this.value=\'' . addcslashes($data, '\'') . '\';';
                }
            }
        }
        $html = '<button ' . $this -> _hc('button', $field -> getClass(), $nameIndex)
            . ' id="' . $name = $this -> idPath($field -> getName(), $nameIndex) . '"'
            . 'name="' . $field -> getName() . '" value="" type="submit"';
        foreach ($attrs as $attr => $data) {
            $html .= ' ' . $attr . '="' . $data . '"';
        }
        $html .= '>' . $label . '</button>';
        return $html;
    }

    /**
     * Short form for returning a HTML class reference
     */
    function _hc($base, $classes = '', $index = 0) {
        if ($this -> _useStyles) {
            if ($classes) {
                if (is_array($classes)) {
                    $index = $index % count($classes);
                    if ($index < 0) {
                        $index += count($classes);
                    }
                    $custom = ' ' . $classes[$index];
                } else {
                    $custom = ' ' . $classes;
                }
            } else {
                $custom = '';
            }
            return ' class="' . $this -> _stylePrefix . $base . $custom. '" ';
        }
        return '';
    }

    function _optElement(&$form, $msg, $hopen = '', $class = '') {
        $html = '';
        if ($seg = $form -> getMessage($msg)) {
            if ($hopen) {
                $tags = explode(' ', $hopen);
                $classes = explode(' ', $class);
                while(count($classes) < count($tags)) {
                    array_unshift($classes, '');
                }
                for ($ind = 0; $ind < count($tags); ++$ind) {
                    $html .= '<' . $tags[$ind];
                    if ($classes[$ind]) {
                        $html .= $this -> _hc($classes[$ind]);
                    }
                    $html .= '>';
                }
                $html .= $seg;
                for ($ind = count($tags) - 1; $ind >= 0; --$ind) {
                    $html .= '</' . $tags[$ind] . '>';
                }
            }
            $html .= chr(10);
        }
        return $html;
    }

    function generateConfirm(&$form) {
        $this -> controlPath = array($form -> getIdPrefix());
        $html = $this -> _htmlPre;
        $html .= '<div ' . $this -> _hc('wrapper') . '>' . chr(10)
            . $this -> _optElement($form, 'div.header', 'span', 'header')
            . '<form id="' . $form -> getIdPrefix() . $form -> getId() . '"' . $this -> _hc('form')
            . 'method="post" action="' . $form -> action .'">';
        if (! $form -> getStandAlone()) {
            $html .= '<div><input id="' . $form -> getIdPrefix() . 'formID"'
                .' name="formID" type="hidden" value="AP5L_Forms_Rapid"/></div>';
        }
        $container = $form -> getContainer();
        $html .= $this -> _generateConfirmContainer($form, $container);
        $html .= '</form>' . chr(10)
            . $this -> _optElement($form, 'div.footer', 'span', 'footer')
            . '</div>';
        $html .= $this -> _htmlPost;
        return $html;
    }

    /**
     * Generate the form with editable controls.
     *
     * @param AP5L_Forms_Rapid The form definition
     * @return string The form's HTML.
     */
    function generateEditable(&$form) {
        $this -> controlPath = array($form -> getIdPrefix());
        $html = $this -> _htmlPre;
        $html .= '<div ' . $this -> _hc('wrapper') . '>' . chr(10)
            . $this -> _optElement($form, 'div.header', 'span', 'header')
            . '<form id="' . $form -> getIdPrefix() . $form -> getId() . '"' . $this -> _hc('form')
            . 'method="post" action="' . $form -> action .'">';
        if (! $form -> getStandAlone()) {
            $html .= '<div><input id="' . $form -> getIdPrefix() . 'formID"'
                .' name="formID" type="hidden" value="AP5L_Forms_Rapid"/></div>';
        }
        $container = $form -> getContainer();
        $html .= $this -> _generateEditableContainer($form, $container);
        $html .= '</form>' . chr(10)
            . $this -> _optElement($form, 'div.footer', 'span', 'footer')
            . '</div>';
        $html .= $this -> _htmlPost;
        return $html;
    }

    function setErrorWrapper($pre = '', $post = '') {
        $this -> _errMsgPre = $pre;
        $this -> _errMsgPost = $post;
    }

    function setFormWrapper($pre = '', $post = '') {
        $this -> _htmlPre = $pre;
        $this -> _htmlPost = $post;
    }

    function setHelpWrapper($pre = '', $post = '') {
        $this -> _tipPre = $pre;
        $this -> _tipPost = $post;
    }

    function setImagePath($path) {
        $this -> _imagePath = $path;
    }

    function setRequiredWrapper($pre = '', $post = '') {
        $this -> _requiredPre = $pre;
        $this -> _requiredPost = $post;
    }

    function setStylePrefix($prefix) {
        $this -> _stylePrefix = $prefix;
    }

}
