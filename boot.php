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
    rex_extension::register('PACKAGES_INCLUDED', function() {
        rex::setProperty('mform_yform', \Kreatif\Form::factory('', false));
    });

    // kreatif: yform saving
    function processYformForms(rex_extension_point $ep) {
        $fragPath = rex_post('mform_yform_fragment_path', 'string');
        $valueId = rex_post('mform_yform_value_id', 'int');

        if ($fragPath && $valueId) {
            $sql = rex_sql::factory();
            $sliceId = $ep->getParam('slice_id');
            $yform = rex::getProperty('mform_yform');
            $fragment = new rex_fragment();
            $fragment->setVar('yform', $yform);
            $fragment->parse($fragPath);

            $yform->getForm();
            $values = $yform->getFormEmailValues();

            $sql->setTable('rex_article_slice');
            $sql->setValue('value' . $valueId, json_encode($values));
            $sql->setWhere(['id' => $sliceId]);
            $sql->update();
        }
    }

    rex_extension::register('SLICE_ADDED', 'processYformForms');
    rex_extension::register('SLICE_UPDATED', 'processYformForms');


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
