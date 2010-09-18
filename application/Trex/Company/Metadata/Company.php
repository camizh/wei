<?php
/**
 * Company
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
 * @subpackage  Company
 * @author      Twin Huang <twinh@yahoo.cn>
 * @copyright   Twin Huang
 * @license     http://www.opensource.org/licenses/apache2.0.php Apache License
 * @version     $Id$
 * @since       2010-09-18 23:15:46
 */

class Trex_Company_Metadata_Company extends Trex_Metadata
{
    public function  __construct()
    {
        $this->setCommonMetadata();
        $this->parseMetadata(array(
            'field' => array(
                'member_id' => array(
                    'basic' => array(
                        'title' => 'LBL_FIELD_MEMBER_NAME',
                    ),
                    'form' => array(
                        '_type' => 'select',
                        '_resourceGetter' => array(
                            array('Project_Hepler_Category', 'getTreeResource'),
                            array(
                                'namespace' => 'Trex',
                                'module' => 'Member',
                                'controller' => 'Member',
                            ),
                            null,
                            array('id', null, 'username'),
                        ),
                    ),
                    'attr' => array(
                        'isListLink' => 1,
                    ),
                ),
                'name' => array(

                ),
                'industry' => array(
                    'form' => array(
                        '_type' => 'select',
                        '_resourceGetter' => array(
                            array('Project_Helper_CommonClass', 'get'),
                            'company-industry',
                        ),
                    ),
                    'converter' => array(
                        'list' => array(
                            array('Project_Helper_CommonClass', 'convert'),
                            'company-industry',
                        ),
                        'view' => array(
                            array('Project_Helper_CommonClass', 'convert'),
                            'company-industry',
                        )
                    ),
                ),
                'nature' => array(
                    'form' => array(
                        '_type' => 'select',
                        '_resourceGetter' => array(
                            array('Project_Helper_CommonClass', 'get'),
                            'company-nature',
                        ),
                    ),
                    'converter' => array(
                        'list' => array(
                            array('Project_Helper_CommonClass', 'convert'),
                            'company-nature',
                        ),
                        'view' => array(
                            array('Project_Helper_CommonClass', 'convert'),
                            'company-nature',
                        )
                    ),
                ),
                'size' => array(
                    'form' => array(
                        '_type' => 'select',
                        '_resourceGetter' => array(
                            array('Project_Helper_CommonClass', 'get'),
                            'company-size',
                        ),
                    ),
                    'converter' => array(
                        'list' => array(
                            array('Project_Helper_CommonClass', 'convert'),
                            'company-size',
                        ),
                        'view' => array(
                            array('Project_Helper_CommonClass', 'convert'),
                            'company-size',
                        )
                    ),
                ),
                'address' => array(

                ),
                'description' => array(
                    'form' => array(
                        '_type' => 'textarea',
                    ),
                ),
            ),
            'model' => array(
                'member' => array(
                    'name' => 'Trex_Member_Model_Member',
                    'alias' => 'member',
                    'metadata' => 'Trex_Member_Metadata_Member',
                    'local' => 'member_id',
                    'foreign' => 'id',
                    'type' => 'view',
                    'fieldMap' => array(
                        'member_id' => 'username',
                    ),
                ),
            ),
            'db' => array(
                'table' => 'company',
                'order' => array(
                    array('date_created', 'DESC'),
                ),
            ),
            'page' => array(
                'title' => 'LBL_MODULE_COMPANY',
            ),
        ));
    }
}