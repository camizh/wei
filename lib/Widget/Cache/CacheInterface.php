<?php
/**
 * Widget Framework
 *
 * @copyright   Copyright (c) 2008-2013 Twin Huang
 * @license     http://www.opensource.org/licenses/apache2.0.php Apache License
 */

namespace Widget\Cache;

/**
 * The base cache interface
 *
 * @author      Twin Huang <twinhuang@qq.com>
 */
interface CacheInterface
{
    /**
     * Get or store an item
     *
     * @param  string      $key    The name of item
     * @param  mixed       $value  The value of item
     * @param  int         $expire The expire seconds, 0 means never expired
     * @return mixed
     */
    public function __invoke($key, $value = null, $expire = 0);

    /**
     * Get an item
     *
     * @param  string      $key The name of item
     * @return mixed
     */
    public function get($key);

    /**
     * Store an item
     *
     * @param  string $key    The name of item
     * @param  value  $value  The value of item
     * @param  int    $expire The expire seconds, 0 means never expired
     * @return bool
     */
    public function set($key, $value, $expire = 0);

    /**
     * Remove an item
     *
     * @param  string $key The name of item
     * @return bool
     */
    public function remove($key);
    
    /**
     * Check if an item is exists
     * 
     * @param string $key
     * @return bool
     */
    public function exists($key);

    /**
     * Add an item
     *
     * @param  string $key    The name of item
     * @param  mixed  $value  The value of item
     * @param  int    $expire The expire seconds, 0 means never expired
     * @return bool
     */
    public function add($key, $value, $expire = 0);

    /**
     * Replace an existing item
     *
     * @param  string $key    The name of item
     * @param  mixed  $value  The value of item
     * @param  int    $expire The expire seconds, 0 means never expired
     * @return bool
     */
    public function replace($key, $value, $expire = 0);

    /**
     * Increment an item
     *
     * @param  string    $key    The name of item
     * @param  int       $offset The value to increased
     * @return int|false Returns the new value on success, or false on failure
     */
    public function increment($key, $offset = 1);

    /**
     * Decrement an item
     *
     * @param  string    $key    The name of item
     * @param  int       $offset The value to be decreased
     * @return int|false Returns the new value on success, or false on failure
     */
    public function decrement($key, $offset = 1);

    /**
     * Clear all items
     *
     * @return boolean
     */
    public function clear();
}
