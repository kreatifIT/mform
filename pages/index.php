<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

if (rex_be_controller::getCurrentPage() != 'mform/tablemap') {
    echo rex_view::title(rex_i18n::msg('mform_title') . ' ' . rex_i18n::msg('mform_'.rex_be_controller::getCurrentPagePart(2)));
}
rex_be_controller::includeCurrentPageSubPath();

