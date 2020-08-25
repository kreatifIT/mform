<?php

/**
 * REX_CUSTOM_LINK.
 *
 * Attribute:
 *   - category  => Kategorie in die beim oeffnen der Linkmapw gesprungen werden soll
 *
 * @package redaxo\structure
 */
class rex_var_custom_link extends rex_var
{
    public static function getCustomLinkText($value, $args = [])
    {
        $valueName = $value;
        if (file_exists(rex_path::media($value)) === true) {
            // do nothing
        } else if (filter_var($value, FILTER_VALIDATE_URL) === FALSE && is_numeric($value)) {
            // article!
            $art = rex_article::get((int)$value);
            if ($art instanceof rex_article) {
                $valueName = trim(sprintf('%s [%s]', $art->getName(), $art->getId()));
            }
        } else if (substr($value, 0, 6) == 'ytable') {
            // yform table!
            list($table, $id) = explode('|', substr($value, 9));
            $yArgs = rex_var::toArray($args['ytables']);
            foreach ($yArgs as $yArg) {
                if ($yArg[0] == $table) {
                    $fields = $yArg[1];
                    break;
                }
            }

            if ($fields) {
                $yTable = rex_yform_manager_table::get($table);
                $stmt = rex_yform_manager_dataset::query($table);
                $stmt->resetSelect();
                $stmt->selectRaw('id, CONCAT('. $fields .') AS label');
                $stmt->where('id', $id);
                $item = $stmt->findOne();
                if ($item) {
                    $valueName = rex_i18n::translate($yTable->getName()) .': '. $item->getValue('label');
                }
            }
        }
        return $valueName;
    }

    protected function getOutput()
    {
        $id = $this->getArg('id', 0, true);
        if (!in_array($this->getContext(), ['module', 'action']) || !is_numeric($id) || $id < 1 || $id > 10) {
            return false;
        }

        $value = $this->getContextData()->getValue('linklist' . $id);

        if ($this->hasArg('isset') && $this->getArg('isset')) {
            return $value ? 'true' : 'false';
        }

        if ($this->hasArg('widget') && $this->getArg('widget')) {
            if (!$this->environmentIs(self::ENV_INPUT)) {
                return false;
            }
            $args = [];
            foreach (['category', 'media', 'media_category', 'types', 'external', 'mailto', 'intern', 'phone', 'ytables'] as $key) {
                if ($this->hasArg($key)) {
                    $args[$key] = $this->getArg($key);
                }
            }
            $value = self::getWidget($id, 'REX_INPUT_LINKLIST[' . $id . ']', $value, $args);
        } else {
            if ($value && $this->hasArg('output') && $this->getArg('output') != 'id') {
                $value = rex_getUrl($value);
            }
        }

        return self::quote($value);
    }

    public static function getWidget($id, $name, $value, array $args = [], $btnIdUniq = true)
    {
        $anchorValue = 0;
        if (filter_var($value, FILTER_VALIDATE_URL) === FALSE && is_numeric($value) && strpos($value, '.')) {
            $anchorValue = $value;
            list($value) = explode('.', $value);
        }
        $valueName = self::getCustomLinkText($value, $args);
        $category = '';
        $mediaCategory = '';
        $types = '';

        if (filter_var($value, FILTER_VALIDATE_URL) === FALSE && is_numeric($value)) {
            $art = rex_article::get((int)$value);
            if ($art instanceof rex_article) {
                $category = $art->getCategoryId();
            }
        }

        if (is_numeric($category) || isset($args['category']) && ($category = (int)$args['category'])) {
            $category = ' data-category="' . $category . '"';
        }
        if (isset($args['media_category']) && ($mediaCategory = (int)$args['media_category'])) {
            $mediaCategory = ' data-media_category="' . $mediaCategory . '"';
        }
        if (isset($args['types']) && ($types = $args['types'])) {
            $types = ' data-types="' . $types . '"';
        }

//        $class = (rex::getUser()->getComplexPerm('structure')->hasStructurePerm()) ? '' : ' rex-disabled';
        $class = '';
        $mediaClass = (isset($args['media']) && $args['media'] == 0) ? ' hidden' : $class;
        $externalClass = (isset($args['external']) && $args['external'] == 0) ? ' hidden' : $class;
        $emailClass = (isset($args['mailto']) && $args['mailto'] == 0) ? ' hidden' : $class;
        $linkClass = (isset($args['intern']) && $args['intern'] == 0) ? ' hidden' : $class;
        $tableClass = (isset($args['intern']) && $args['intern'] == 0) ? ' hidden' : $class;
        $phoneClass = (isset($args['phone']) && $args['phone'] == 0) ? ' hidden' : $class;
        $_ytables = rex_var::toArray($args['ytables']);
        $ytables = [];

        foreach ($_ytables as $ytable) {
            $_table = rex_yform_manager_table::get($ytable[0]);
            if ($_table) {
                $ytables[] = implode(':', $ytable);
            }
        }


        if ($btnIdUniq === true) {
            $id = uniqid($id);
        }

        $e = [];
        $e['field'] = '<input class="form-control" type="text" name="REX_LINK_NAME[' . $id . ']" value="' . rex_escape($valueName) . '" id="REX_LINK_' . $id . '_NAME" readonly="readonly" /><input type="hidden" name="' . $name . '" id="REX_LINK_' . $id . '" value="' . $value . '" />';
        $e['functionButtons'] = '
        <a href="#" class="btn btn-popup' . $mediaClass . '" id="mform_media_' . $id . '" title="' . rex_i18n::msg('var_media_open') . '"><i class="rex-icon fa-file-o"></i></a>
        <a href="#" class="btn btn-popup' . $externalClass . '" id="mform_extern_' . $id . '" title="' . rex_i18n::msg('var_extern_link') . '"><i class="rex-icon fa-external-link"></i></a>
        <a href="#" class="btn btn-popup' . $emailClass . '" id="mform_mailto_' . $id . '" title="' . rex_i18n::msg('var_mailto_link') . '"><i class="rex-icon fa-envelope-o"></i></a>
        <a href="#" class="btn btn-popup' . $phoneClass . '" id="mform_tel_' . $id . '" title="' . rex_i18n::msg('var_phone_link') . '"><i class="rex-icon fa-phone"></i></a>
        <a href="#" class="btn btn-popup' . $linkClass . '" id="mform_link_' . $id . '" title="' . rex_i18n::msg('var_link_open') . '"><i class="rex-icon rex-icon-open-linkmap"></i></a>
        '. (count($ytables) ? '<a href="#" class="btn btn-popup' . $tableClass . '" id="mform_table_' . $id . '" title="' . rex_i18n::msg('var_table_open') . '"><i class="rex-icon rex-icon-table"></i></a>' : '') .'
        <a href="#" class="btn btn-popup' . $class . '" id="mform_delete_' . $id . '" title="' . rex_i18n::msg('var_link_delete') . '"><i class="rex-icon rex-icon-delete-link"></i></a>
        ';

        # $telFragment->appendXML("<a href=\"#\" class=\"btn btn-popup\" id=\"mform_tel_{$item->getId()}\" title=\"" . rex_i18n::msg('var_phone_link') . "\"><i class=\"rex-icon fa-phone\"></i></a>");


        $fragment = new rex_fragment();
        $fragment->setVar('elements', [$e], false);
        return str_replace(
            '<div class="input-group">',
            '<div class="input-group custom-link" ' . $category . $types . $mediaCategory . ' data-clang="' . rex_clang::getCurrentId() . '" data-id="' . $id . '" data-anchor-value="'. $anchorValue .'" data-anchor-index="'. $args['anchor_index'] .'" data-ytables="'. rex_escape(implode('|', $ytables), 'html_attr') .'">',
            $fragment->parse('core/form/widget.php')
        );
    }
}




/*
 *
 *

<div class="input-group custom-link" data-clang="1" data-id="2601customlinkf">
    <input class="form-control" id="REX_LINK_2601customlinkf_NAME" name="REX_LINK_NAME[2601customlinkf]" readonly="readonly" type="text" value="">
    <input id="REX_LINK_2601customlinkf" name="REX_INPUT_VALUE[1][customlinkf]" type="hidden" value="">
    <span class="input-group-btn">
        <a class="btn btn-popup" href="#" id="mform_media_2601customlinkf" title="Medium auswählen"><i class="rex-icon fa-file-o"></i></a>
        <a class="btn btn-popup" href="#" id="mform_extern_2601customlinkf" title="Externe URL verlinken"><i class="rex-icon fa-external-link"></i></a>
        <a class="btn btn-popup" href="#" id="mform_link_2601customlinkf" title="Link auswählen"><i class="rex-icon rex-icon-open-linkmap"></i></a>
        <a class="btn btn-popup" href="#" id="mform_delete_2601customlinkf" title="Ausgewählten Link löschen"><i class="rex-icon rex-icon-delete-link"></i></a>
    </span>
</div>

 *
 *
 */