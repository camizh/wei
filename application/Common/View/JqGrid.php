<?php
/**
 * JqGrid
 *
 * Copyright (c) 2008-2010 Twin Huang. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package     Common
 * @subpackage  View
 * @author      Twin Huang <twinh@yahoo.cn>
 * @copyright   Twin Huang
 * @license     http://www.opensource.org/licenses/apache2.0.php Apache License
 * @version     $Id$
 * @since       2010-09-17 18:15:51
 */

class Common_View_JqGrid extends Qwin_Application_View_Processer
{   
    public function __construct(Qwin_Application_View $view)
    {
        // 初始变量,方便调用
        $primaryKey = $view->primaryKey;
        $meta       = $view->meta;
        $metaHepler = $view->metaHelper;
        $request    = Qwin::run('#request');
        $lang       = Qwin::run('-lang');
        $config     = Qwin::run('-config');
        $url        = Qwin::run('-url');
        $asc        = $config['asc'];
        $jqGridHepler = new Common_Helper_JqGrid();

        
        $jqGrid = array();
        $jqGrid['url'] = '?' . $url->arrayKey2Url(array('json' => '1') + $_GET);

        // 获取栏数据
        $jqGrid['colNames'] = array();
        $jqGrid['colModel'] = array();
        foreach ($view->layout as $field) {
            if (is_array($field)) {
                $fieldMeta = $meta['metadata'][$field[0]]['field'][$field[1]];
                $field = $field[0] . '_' . $field[1];                
            } else {
                $fieldMeta = $meta['field'][$field];
            }
            $jqGrid['colNames'][] = $lang->t($fieldMeta['basic']['title']);
            $jqGrid['colModel'][] = array(
                'name' => $field,
                'index' => $field,
            );
            // 隐藏主键
            if ($primaryKey == $field) {
                $jqGrid['colModel'][count($jqGrid['colModel']) - 1]['hidden'] = true;
            }
            // 宽度控制
            if (isset($fieldMeta['list']) && isset($fieldMeta['list']['width'])) {
                $jqGrid['colModel'][count($jqGrid['colModel']) - 1]['width'] = $fieldMeta['list']['width'];
            }
        }
        
        // 排序
        if(!empty($meta['db']['order'])) {
            $jqGrid['sortname']  = $meta['db']['order'][0][0];
            $jqGrid['sortorder'] = $meta['db']['order'][0][1];
        } else {
            $jqGrid['sortname']  = $primaryKey;
            $jqGrid['sortorder'] = 'DESC';
        }

        $jqGrid['datatype']      = 'json';
        $jqGrid['rowNum']        = $request->getLimit();
        $jqGrid['rowNum']        <= 0 && $jqGrid['rowNum'] = $view->meta['db']['limit'];
        $jqGrid['prmNames'] = array(
            'page'              => $request->getOption('page'),
            'rows'              => $request->getOption('row'),
            'sort'              => $request->getOption('orderField'),
            'order'             => $request->getOption('orderType'),
            'search'            => $request->getOption('search'),
        );
        $jqGrid['pager']         = '#ui-jqgrid-page';

        // 弹出窗口配置
        if ($view['isPopup']) {
            $popup = array(
                'valueInput' => $request->r('qw-popup-value-input'),
                'viewInput' => $request->r('qw-popup-view-input'),
                'valueColumn' => $request->r('qw-popup-value-column'),
                'viewColumn' => $request->r('qw-popup-view-column'),
            );
            $jqGrid['multiselect'] = false;
            $jqGrid['autowidth'] = false;
            $jqGrid['width'] = 800;
        }

        $jqGrid = $jqGridHepler->render($jqGrid);
        $jqGridJson = Qwin_Helper_Array::jsonEncode($jqGrid);

        $view->setDataList(get_defined_vars());
    }
}