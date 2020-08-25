<?php

// ------- Default Values

$opener_input_field      = rex_request('opener_input_field', 'string');
$opener_input_field_name = $opener_input_field . '_NAME';
$clangId                 = rex_request('clang', 'int');
$tableString             = rex_request('tables', 'string');
$tables                  = array_filter(explode('|', $tableString));
$currentTable            = rex_request('ctable', 'string', current($tables));
$clang                   = rex_clang::exists($clangId) ? $clangId : rex_clang::getStartId();

$content = '';

$fragment = new rex_fragment();
$fragment->setVar('heading', rex_i18n::msg('yform_manager_table'), false);
echo $fragment->parse('core/page/header.php');

if (count($tables) > 1) {
    $options = [];
    foreach ($tables as $_table) {
        list($table, $columns) = explode(':', $_table);
        $yTable    = rex_yform_manager_table::get($table);
        $options[] = "<option value='{$_table}' ". ($currentTable == $_table ? 'selected="selected"' : '') .">" . rex_i18n::translate($yTable->getName()) . "</option>";
    }
    $changeUrl = rex_url::currentBackendPage([
        'opener_input_field' => $opener_input_field,
        'clang'              => $clangId,
        'tables'             => $tableString,
        'ctable'             => '',
    ]);
    $content   .= '<select class="form-control" onchange="javascript:changeTable(this)">' . implode('', $options) . '</select>';

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('mform_choose_table'), false);
    $fragment->setVar('content', $content, false);
    echo $fragment->parse('core/page/section.php');
}

list($cTable, $cColumns) = explode(':', $currentTable);
$yTable = rex_yform_manager_table::get($cTable);

$list = rex_list::factory("SELECT id, CONCAT({$cColumns}) AS label FROM {$cTable} ORDER BY {$yTable->getSortFieldName()} {$yTable->getSortOrderName()}");
$list->addColumn('functions', '###id###');
$list->setColumnLabel('label', rex_i18n::msg('yform_values_defaults_label'));
$list->setColumnLabel('functions', rex_i18n::msg('pool_file_functions'));
$list->setColumnFormat('functions', 'custom', function ($params) {
    $url = 'javascript:insertLink(\'ytable://' . $params['params']['table'] . '|' . $params['list']->getValue('id') . '\',\'' . rex_escape(trim($params['list']->getValue('label')), 'js') . '\');';
    return '<a href="' . $url . '">'. rex_i18n::msg('pool_get_selectedmedia') .'</a>';
}, ['table' => $cTable]);

$list->addParam('opener_input_field', $opener_input_field);
$list->addParam('clang', $clangId);
$list->addParam('tables', $tableString);
$list->addParam('ctable', $currentTable);

echo $list->get();

?>
<script type="text/javascript">
    function insertLink(link, name) {
        var event = opener.jQuery.Event("rex:selectLink");
        opener.jQuery(window).trigger(event, [link, name]);
        if (!event.isDefaultPrevented()) {
            var linkid = link.replace("redaxo://", "");
            window.opener.document.getElementById("<?= $opener_input_field ?>").value = linkid;
            window.opener.document.getElementById("<?= $opener_input_field_name ?>").value = name;
            self.close();
        }
    }
    function changeTable(_this) {
        var value = $(_this).val();
        window.location.href = '<?= html_entity_decode($changeUrl) ?>' + value;
    }
</script>

