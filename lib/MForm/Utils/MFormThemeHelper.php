<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace MForm\Utils;


use rex_file;
use rex_path;

class MFormThemeHelper
{
    /**
     * @return array
     * @author Joachim Doerr
     */
    public static function getThemesInformation()
    {
        $themeInfo = array();
        $path = implode('/', array('templates'));
        foreach (scandir(rex_path::addonData('mform', $path)) as $item) {
            if ($item == '.' or $item == '..') {
                continue;
            }
            $path = implode('/', array('templates', $item));
            if (is_dir(rex_path::addonData('mform', $path))) {
                $dirName = explode('_', $item);
                $themeInfo[$item] = array(
                    'theme_name' => $dirName[0],
                    'theme_screen_name' => ucwords(str_replace('_', ' ', $item)),
                    'theme_path' => $item
                );
                foreach (scandir(rex_path::addonData('mform', $path)) as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) == 'css') {
                        $path = implode('/', array('templates', $item, $file));
                        $themeInfo[$item]['theme_css_data'][] = rex_path::addonData('mform', $path);

                        if (file_exists(rex_path::addonAssets('mform', $path))) {
                            $themeInfo[$item]['theme_css_assets'][] = array(
                                'full_path' => rex_path::addonAssets('mform', $path),
                                'path' => $path
                            );
                        }
                    }
                }
            }
        }
        // return theme info array
        return $themeInfo;
    }

    /**
     * @author Joachim Doerr
     */
    public static function copyThemeCssToAssets()
    {
        // copy all theme css files to assets folder
        foreach (self::getThemesInformation() as $theme) {
            if (array_key_exists('theme_css_data', $theme)) {
                #rex_file::copy($theme, );
                foreach ($theme['theme_css_data'] as $css) {
                    rex_file::copy($css, rex_path::addonAssets('mform', implode('/', array('templates', $theme['theme_path'], pathinfo($css, PATHINFO_BASENAME)))));
                }
            }
        }
    }

    /**
     * @param $theme
     * @return array
     * @author Joachim Doerr
     */
    public static function getCssAssets($theme)
    {
        $themeInfo = self::getThemesInformation();
        $cssList = array();
        if (array_key_exists($theme, $themeInfo) && array_key_exists('theme_css_assets', $themeInfo[$theme])) {
            foreach ($themeInfo[$theme]['theme_css_assets'] as $css) {
                $cssList[] = $css['path'];
            }
        }
        return $cssList;
    }

    /**
     * @param $theme
     * @author Joachim Doerr
     */
    public static function themeBootCheck($theme)
    {
        $themeInfo = self::getThemesInformation();
        if (array_key_exists($theme, $themeInfo)
            && (
                !array_key_exists('theme_css_assets', $themeInfo[$theme])
                && array_key_exists('theme_css_data', $themeInfo[$theme])
            )
        ) {
            self::copyThemeCssToAssets();
        }
    }
}