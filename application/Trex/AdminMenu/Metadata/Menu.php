<?php
/**
 * AdminMenu
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
 * @package     Trex
 * @subpackage  AdminMenu
 * @author      Twin Huang <twinh@yahoo.cn>
 * @copyright   Twin Huang
 * @license     http://www.opensource.org/licenses/apache2.0.php Apache License
 * @since       2010-05-25 08:00:36
 */

class Trex_AdminMenu_Metadata_Menu extends Trex_Metadata
{
    public function  __construct()
    {
        $this->setCommonMetadata();
        $this->parseMetadata(array(
            // 基本属性
            'field' => array(
                'category_id' => array(
                    'form' => array(
                        '_type' => 'select',
                        '_resourceGetter' => array(
                            array('Project_Hepler_Category', 'getTreeResource'),
                            array(
                                'namespace' => 'Trex',
                                'module' => 'AdminMenu',
                                'controller' => 'Menu',
                            ),
                            null,
                            array('id', 'category_id', 'title'),
                        ),
                    ),
                    'attr' => array(
                        'isLink' => 1,
                    ),
                    'converter' => array(
                        'list' => array(
                            array('Project_Hepler_Category', 'convertTreeResource'),
                            array(
                                'namespace' => 'Trex',
                                'module' => 'AdminMenu',
                                'controller' => 'Menu',
                            ),
                            NULL,
                            array('id', 'category_id', 'title'),
                        ),
                        'view' => 'list',
                    ),
                ),
                'title' => array(
                    'validator' => array(
                        'required',
                        'maxlength,40',
                    ),
                ),
                'url' => array(
                    'attr' => array(
                        'isLink' => 0,
                        'isList' => 1,
                        'isDbField' => 1,
                        'isDbQuery' => 1,
                    ),
                    'validator' => array(
                        'required',
                        'maxlength,256',
                    ),
                ),
                'target' => array(
                    'form' => array(
                        '_value' => '_self',
                    ),
                    'attr' => array(
                        'isLink' => 1,
                    ),
                    'validator' => array(
                        'required',
                        'maxlength,16',
                    ),
                ),
                'order' => array(
                    'attr' => array(
                        'isLink' => 1,
                    ),
                ),
            ),
            'group' => array(
            ),
            'model' => array(
                'creator' => array(
                    'set' => array(
                        'module' => 'Member',
                        'controller' => 'Member',
                    ),
                    'alias' => 'creator',
                    'local' => 'created_by',
                    'type' => 'view',
                    'fieldMap' => array(
                        'created_by' => 'username',
                    ),
                ),
                'modifier' => array(
                    'set' => array(
                        'module' => 'Member',
                        'controller' => 'Member',
                    ),
                    'alias' => 'modifier',
                    'local' => 'modified_by',
                    'type' => 'view',
                    'fieldMap' => array(
                        'modified_by' => 'username',
                    ),
                ),
            ),
            'metadata' => array(
            ),
            'db' => array(
                'table' => 'admin_menu',
                'order' => array(
                    array('order', 'DESC')
                ),
                'limit' => 20,
            ),
            'page' => array(
                'title' => 'LBL_MODULE_ADMIN_MENU',
            ),
        ));
    }
}
