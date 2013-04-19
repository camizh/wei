<?php
/**
 * Widget Framework
 *
 * @copyright   Copyright (c) 2008-2013 Twin Huang
 * @license     http://www.opensource.org/licenses/apache2.0.php Apache License
 */

namespace Widget\Validator;

/**
 * Check if the input is valid by any of the rules
 * 
 * @author      Twin Huang <twinhuang@qq.com>
 */
class OneOf extends SomeOf
{
    protected $atLeastMessage = '%name% must be passed by at least one rule';
    
    protected $atLeast = 1;
}
