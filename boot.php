<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

use MForm\Utils\MFormThemeHelper;

if (rex_addon::exists('yform') &&
    rex_addon::get('yform')->isAvailable() &&
    rex_plugin::get('yform', 'manager')->isAvailable()) {
    rex_yform::addTemplatePath(rex_path::addon('mform', 'data/ytemplates'));
    rex_extension::register('MEDIA_IS_IN_USE', 'MformYformHelper::isMediaInUse');
}

if (rex::isBackend()) {
    // kreatif: yform saving
    if (rex_addon::get('kreatif')->isAvailable()) {
        function processYformForms(rex_extension_point $ep) {
            $callbacks = json_decode(rex_post('mform_field_callbacks', 'string'), true);
            $mapping = json_decode(rex_post('mform_field_mapping', 'string'), true);

            if ($callbacks && $mapping) {
                $sql = rex_sql::factory();
                $sql->setTable('rex_article_slice');
                $sliceId = $ep->getParam('slice_id');

                $yform = \Kreatif\Form::factory('slice-yform-'. $sliceId, false);
                $yform->setObjectparams('form_fragment', 'kreatif/backend/mform/empty_mform_form.php');
                $yform->setObjectparams('use_form_tag', false);
                $yform->setObjectparams('send', 1);

                foreach ($callbacks as $parameter) {
                    $parameter['yform'] = $yform;
                    $yform = call_user_func($parameter['function'], $parameter);
                }

                $yform->getForm();
                $values = $yform->getFormEmailValues();

                $_values = [];
                foreach ($mapping as $fieldname => $valueId) {
                    $_values[$valueId][$fieldname] = $values[$fieldname];
                }
                foreach ($_values as $valueId => $values) {
                    $sql->setValue('value' . $valueId, json_encode($values));
                }
                $sql->setWhere(['id' => $sliceId]);
                $sql->update();
            }
        }

        rex_extension::register('STRUCTURE_CONTENT_BEFORE_SLICES', function(rex_extension_point $ep) {
            $yform = \Kreatif\Form::factory('slice-yform-'. $ep->getParam('slice_id'), false);
            $yform->setObjectparams('form_fragment', 'kreatif/backend/mform/empty_mform_form.php');
            $yform->setObjectparams('use_form_tag', false);
            rex::setProperty('mform_yform', $yform);
        });
        rex_extension::register('mform.showOutput', function(rex_extension_point $ep) {
            $output = $ep->getSubject();
            $yform = rex::getProperty('mform_yform');
            $callbacks = $yform->getObjectparams('mform_field_callbacks');
            $mapping = $yform->getObjectparams('mform_field_mapping');
            $values = $yform->getObjectparams('mform_values');

            if ($yform->isSend()) {
                $yform->regenerateForm();
            } else {
                $yform->setObjectparams('data', $values);
                $yform->getForm();
            }
            $fields = $yform->getValueFields();

            foreach ($fields as $fieldname => $field) {
                $fieldContent = '<div class="form-fields">'. $field->getElement('field_output') .'</div>';
                $output = str_replace("{{YFORM-FIELD-{$fieldname}}}", $fieldContent, $output);
            }
            $output .= '<input type="hidden" name="mform_field_callbacks" value="'. rex_escape(json_encode($callbacks), 'html_attr') .'"/>';
            $output .= '<input type="hidden" name="mform_field_mapping" value="'. rex_escape(json_encode($mapping), 'html_attr') .'"/>';
            $ep->setSubject($output);
        });
        rex_extension::register('SLICE_ADDED', 'processYformForms');
        rex_extension::register('SLICE_UPDATED', 'processYformForms');
    }


    // check theme css is exists
    MFormThemeHelper::themeBootCheck(rex_addon::get('mform')->getConfig('mform_theme'));

    // use theme helper class
    if(MFormThemeHelper::getCssAssets(rex_addon::get('mform')->getConfig('mform_theme'))) {
        // foreach all css files
        foreach (MFormThemeHelper::getCssAssets(rex_addon::get('mform')->getConfig('mform_theme')) as $css) {
            // add assets css file
            rex_view::addCssFile($this->getAssetsUrl($css));
        }
    }
    // add toggle files
    rex_view::addCssFile($this->getAssetsUrl('toggle/toggle.css'));
    rex_view::addJsFile($this->getAssetsUrl('toggle/toggle.js'));
    // widgets
    rex_view::addCssFile($this->getAssetsUrl('css/imglist.css'));
    rex_view::addJsFile($this->getAssetsUrl('js/imglist.js'));
    rex_view::addJsFile($this->getAssetsUrl('js/customlink.js'));
    // add mform js
    rex_view::addJsFile($this->getAssetsUrl('mform.js'));

    // reset count per page init
    if (rex_backend_login::hasSession()) {
        rex_set_session('mform_count', 0);
    }
}
