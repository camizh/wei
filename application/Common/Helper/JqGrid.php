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
 * @author      Twin Huang <twinh@yahoo.cn>
 * @copyright   Twin Huang
 * @license     http://www.opensource.org/licenses/apache2.0.php Apache License
 * @version     $Id$
 * @since       2011-01-18 15:24:53
 */

class Common_Helper_JqGrid
{
    public function __construct()
    {
        
    }

    /**
     * @var array $jgrid            jgrid 配置,不与js完全一致
     *
     *      -- url                  获取json数据的链接
     *
     *      -- datatype             数据类型
     *
     *      -- colNames             列的名称
     *
     *      -- colModel             列的模型
     *
     *      -- sortname             排序的域名称
     *
     *      -- sortorder            排序的域类型
     *
     *      -- rowNum               每列显示数目
     *
     *      -- rowList              每页显示数目的选项
     *
     *      -- caption              标题
     *
     *      -- rownumbers           是否显示记录为第几行
     *
     *      -- multiselect          是否允许多选
     *
     *      -- height               高度,默认为100%
     *
     *      -- width                宽度,默认为自动(auto)
     *
     *      -- autowidth            是否自动调整高度
     *
     *      -- pager                分页对象的字符串
     *
     *      -- viewrecords          是否在分页栏右下角显示记录数,默认为显示
     *
     *      -- forceFit             列宽度改变改变时,不改变表格宽度,从而不出现滚动条
     * 
     *      -- prmNames             各参数的对应关系
     *
     *          -- page                 分页的查询名称
     *
     *          -- rows                 每页显示数目的查询名称
     *
     *          -- sort                 排序域查询的名称
     *
     *          -- order                排序类型的查询名称
     *
     *          -- search               搜索的查询名称   
     */
    protected $_defaultOption = array(
        'url'           => '',
        'datatype'      => 'json',
        'colNames'      => array(),
        'colModel'      => array(),
        'sortname'      => null,
        'sortorder'     => null,
        'rowNum'        => 10,
        'rowList'       => array('5', '10', '20', '30', '40', '50', '100'),
        'caption'       => false,
        'rownumbers'    => true,
        'multiselect'   => true,
        'height'        => '100%',
        'width'         => 'auto',
        'autowidth'     => true,
        'pager'         => '#ui-jqgrid-page',
        'viewrecords'   => true,
        'forceFit'      => true,
        'toolbar'       => array(true, 'top'),
        //'ondblClickRow' => null,
        'prmNames'      => array(
            'page'      => 'page',
            'rows'      => 'rows',
            'sort'      => 'sort',
            'order'     => 'order',
            'search'    => 'search',
            'nd'        => 'nd',
            'npage'     => 'npage'
        ),
        'toolbarObject' => '#ui-jqgrid-toolbar',
        'object'        => '#ui-jqgrid-table',
        
    );

    public function render(array $option)
    {
        $option = $option + $this->_defaultOption;
        // 只进行简单转换
        $option['objectString'] = substr($option['object'], 1);
        $option['toolbarObjectString'] = substr($option['toolbarObject'], 1);
        $option['pagerString'] = substr($option['pager'], 1);
        return $option;
    }
}