<?php
/**
 * Field
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
 * @package     Qwin
 * @subpackage  Metadata
 * @author      Twin Huang <twinh@yahoo.cn>
 * @copyright   Twin Huang
 * @license     http://www.opensource.org/licenses/apache2.0.php Apache License
 * @version     $Id$
 * @since       2010-7-26 14:07:07
 */

class Qwin_Metadata_Element_Field extends Qwin_Metadata_Element_Abstract
{
    /**
     * 排序的大小,用于自动生成排序值
     * @var int
     */
    //protected $_order = 0;

    /**
     * 排序的每次递增的数量
     * @var int
     */
    //protected $_orderLength = 20;

   /**
     * 查找属性的缓存数组
     * @var array
     */
    protected $_attrCache = array();

    public function getSampleData()
    {
        return array(
            'basic' => array(
                'title' => 'LBL_FIELD_TITLE',
                'description' => array(),
                'order' => 50,
                'group' => 0,
            ),
            'form' => array(
                '_type' => 'text',
                '_resource' => null,
                //'_resourceGetter' => null,
                //'_resourceFormFile' => null,
                '_extend' => null,
                '_value' => '',
                'name' => null,
                'id' => null,
                'class' => null,
            ),
            'attr' => array(
                'isLink' => 0,
                'isList' => 0,
                'isDbField' => 1,
                'isDbQuery' => 1,
                'isReadonly' => 0,
                'isView' => 1,
            ),
            'db' => array(
                'type' => 'string',
                'length' => null,
            ),
            'sanitiser' => array(
            ),
            'validator' => array(
                'rule' => array(),
                'message' => array(),
            ),
        );
    }

    /**
     * 以数组的形式递归格式化数据
     *
     * @return object 当前对象
     */
    public function format()
    {
        return $this->_formatAsArray();
    }

    protected function _format($metadata, $name = null)
    {
        // 转换成数组
        if (is_string($metadata)) {
            $metadata = array(
                'form' => array(
                    'name' => $metadata,
                )
            );
        // 初始化名称
        } else {
            if (!isset($metadata['form'])) {
                $metadata['form'] = array();
            }
            if (!isset($metadata['form']['name'])) {
                if (null != $name) {
                    $metadata['form']['name'] = $name;
                } else {
                    require_once 'Qwin/Metadata/Element/Field/Exception.php';
                    throw new Qwin_Metadata_Element_Field_Exception('The name value is not defined.');
                }
            }
        }

        if (!isset($metadata['basic'])) {
            $metadata['basic'] = array();
        }
        
        // 设置名称
        if (!isset($metadata['basic']['title'])) {
            $metadata['basic']['title'] = 'LBL_FIELD_' . strtoupper($metadata['form']['name']);
        }

        // 设置描述语句
        if (!isset($metadata['basic']['description'])) {
            $metadata['basic']['description'] = array();
        } elseif(!is_array($metadata['basic']['description'])) {
            $metadata['basic']['description'] = array($metadata['basic']['description']);
        }

        // 设置编号
        if (!isset($metadata['form']['id'])) {
            $metadata['form']['id'] = $metadata['form']['name'];
        }

        // 初始验证器和补全验证信息
        if(!isset($metadata['validator'])) {
            $metadata['validator'] = array(
                'rule' => array(),
                'message' => array(),
            );
        } elseif(!empty($metadata['validator']['rule'])) {
            foreach ($metadata['validator']['rule'] as $key => $rule) {
                if (!isset($metadata['validator']['message'][$key])) {
                    $metadata['validator']['message'][$key] = 'MSG_VALIDATOR_' . strtoupper($key);
                }
            }
        }

        // 转换转换器的配置,使不同的行为之间允许共享转换器
        !isset($metadata['sanitiser']) && $metadata['sanitiser'] = array();
        foreach ($metadata['sanitiser'] as $key => $value) {
            if (is_string($value) && isset($metadata['sanitiser'][$value])) {
                $metadata['sanitiser'][$key] = $metadata['sanitiser'][$value];
            }
        }
        
        return $this->_multiArrayMerge($this->getSampleData(), $metadata);
    }

    /**
     * 筛选符合属性的域
     *
     * @param 合法的属性组成的数组
     * @param 非法的属性组成的数组
     * @return array 符合要求的的域组成的数组
     */
    public function getAttrList($allowAttr, $banAttr = null)
    {
        $allowAttr = (array)$allowAttr;
        $banAttr = (array)$banAttr;

        // 查找是否已有该属性的缓存数据
        $cacheName = implode('|', $allowAttr) . '-' . implode('', $banAttr);
        if (isset($this->_attrCache[$cacheName])) {
            return $this->_attrCache[$cacheName];
        }

        $tmpArr = array();
        $result = array();
        foreach ($allowAttr as $attr) {
            $tmpArr[$attr] = 1;
        }
        foreach ($banAttr as $attr) {
            $tmpArr[$attr] = 0;
        }
        foreach ($this->_data as $field) {
            if ($tmpArr == array_intersect_assoc($tmpArr, $field['attr'])) {
                $result[$field['form']['name']] = $field['form']['name'];
            }
        }
        // 加入缓存中
        $this->_attrCache[$cacheName] = $result;
        return $result;
    }

    public function setField($name, $data)
    {
        $this->_data[$name] = $this->_multiArrayMerge($this->_data[$name], $data);
        return $this;
    }

    /**
     * 设置指定域的属性
     *
     * @param string $field 域的名称
     * @param string $attr 属性的名称
     * @param mixed $value 属性的值
     * @return Qwin_Metadata_Element_Field 当前对象
     */
    public function setAttr($field, $attr, $value)
    {
        $this->_data[$field]['attr'][$attr] = $value;
        return $this;
    }

    /**
     * 根据域中的order从小到大排序
     * 
     * @return Qwin_Metadata_Element_Field 当前对象
     * @todo 转为n维数组排序
     */
    public function order()
    {
        $newArr = array();
        foreach ($this->_data as $key => $val) {
            $tempArr[$key] = $val['basic']['order'];
        }
        // 倒序再排列,因为 asort 会使导致倒序
        $tempArr = array_reverse($tempArr);
        asort($tempArr);
        foreach ($tempArr as $key => $val) {
            $newArr[$key] = $this->_data[$key];
        }
        $this->_data = $newArr;
        return $this;
    }

    public function addValidator()
    {

    }

    public function addValidatorRule()
    {

    }

    /**
     * 转换语言
     *
     * @param array $language 用于转换的语言
     * @return Qwin_Metadata_Element_Field 当前对象
     */
    public function translate($language)
    {
        
        foreach($this->_data as &$data) {
            // 转换标题
            $data['basic']['titleCode'] = $data['basic']['title'];
            if (isset($language[$data['basic']['title']])) {
                $data['basic']['title'] = $language[$data['basic']['title']];
            }

            // 转换描述
            $data['basic']['descriptionCode'] = array();
            foreach ($data['basic']['description'] as $key => &$description) {
                $data['basic']['descriptionCode'][$key] = $description;
                if(isset($language[$description]))
                {
                    $description = $language[$description];
                }
            }

            // 转换分组
            $data['basic']['groupCode'] = $data['basic']['group'];
            if (isset($language[$data['basic']['group']])) {
                $data['basic']['group'] = $language[$data['basic']['group']];
            }
        }
        return $this;
    }

    public function filterReadonlyToHidden()
    {
        foreach ($this->_data as $field => $data) {
            if (1 == $data['attr']['isReadonly'] || 'custom' == $data['form']['_type']) {
                $this->_data[$field]['form']['_type'] = 'hidden';
            }
        }
        return $this;
    }
    
    /**
     * 增加表单域的类名
     *
     * @param string $field 域的名称
     * @param string $value 类名,多个类名用空格分开
     * @return object 当前对象
     * @todo [重要]象数组一样自由赋值
     */
    public function addClass($field, $value)
    {
        if ('' != $this->_data[$field]['form']['class']) {
            $value = ' ' . $value;
        }
        $this->_data[$field]['form']['class'] .= $value;
        return $this;
    }

    public function getSecondLevelValue($type)
    {
        $newData = array();
        foreach ($this->_data as $data) {
            $newData[$data['form']['name']] = $data[$type[0]][$type[1]];
        }
        return $newData;
    }
    
    /**
     * 设置验证缩写名称和数组的对应关系
     *
     * @param string $name 缩写
     * @param array $value 全数组
     * @return object 当前对象
     */
    public function setValidatorMap($name, $value)
    {
        $this->_validatorMap[$name] = $value;
        return $this;
    }

    /**
     * 设置指定域为只读
     *
     * @param array|string $data
     * @return object 当前对象
     */
    public function setReadonly($data)
    {
        $data = (array)$data;
        foreach ($data as $key) {
            if (0 == $this->_data[$key]['attr']['isReadonly']) {
                $this->_data[$key]['attr']['isReadonly'] = 1;
                $this->_data[$key]['form']['_type'] = 'hidden';
            }
        }
        return $this;
    }

    /**
     * 为元数据表单名称增加组名,如name将转换为group[name]
     *
     * @param string $name 组名
     * @return object 当前对象
     */
    public function setFormGroupName($name)
    {
        foreach ($this->_data as $key => $value) {
            $this->_data[$key]['form']['_oldName'] = $this->_data[$key]['form']['_name'];
            $this->_data[$key]['form']['id'] = $name . '_' . $this->_data[$key]['form']['_name'];
            $this->_data[$key]['form']['_name'] = $name . '[' . $this->_data[$key]['form']['_name'] . ']';
        }
        return $this;
    }

     /**
     * 为元数据表单名称增加前缀
     *
     * @param string $name 前缀
     * @return object 当前对象
     */
    public function setFormPrefixName($name)
    {
        foreach ($this->_data as $key => $value) {
            $this->_data[$key]['form']['_oldName'] = $this->_data[$key]['form']['_name'];
            $this->_data[$key]['form']['_name'] = $name . $this->_data[$key]['form']['_name'];
        }
        return $this;
    }

    /**
     * 设置除了参数中定义的键名外为只读
     *
     * @param array|string $data
     * @return object 当前对象
     * @todo 通过php数组函数优化
     */
    public function setReadonlyExcept($data)
    {
        $data = (array)$data;
        foreach ($this->_data as $key => $value) {
            if (!in_array($key, $data) && 0 == $this->_data[$key]['attr']['isReadonly']) {
                $this->_data[$key]['attr']['isReadonly'] = 1;
                $this->_data[$key]['form']['_type'] = 'hidden';
            }
        }
        return $this;
    }

    /**
     * 获取表单配置中的初始值
     *
     * @return array
     */
    public function getFormValue()
    {
        $data = array();
        foreach ($this->_data as $name => $field) {
            $data[$name] = $field['form']['_value'];
        }
        return $data;
    }
}
