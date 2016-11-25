<?php

namespace Illuminate\Support;

use ArrayAccess;
use Illuminate\Support\Traits\Macroable;

class Arr
{
    use Macroable;

    /**
     * 排序字段
     */
    private static $__sortField;

    /**
     * 排序类型
     */
    private static $__sortType;

    /**
     * 排序Flag
     */
    private static $__sortFlag;

    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    public static function add($array, $key, $value)
    {
        if (is_null(static::get($array, $key))) {
            static::set($array, $key, $value);
        }

        return $array;
    }

    /**
     * Build a new array using a callback.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     *
     * @deprecated since version 5.2.
     */
    public static function build($array, callable $callback)
    {
        $results = [];

        foreach ($array as $key => $value) {
            list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

            $results[$innerKey] = $innerValue;
        }

        return $results;
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  array  $array
     * @return array
     */
    public static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (! is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * Divide an array into two arrays. One with keys and the other with values.
     *
     * @param  array  $array
     * @return array
     */
    public static function divide($array)
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  array   $array
     * @param  string  $prepend
     * @return array
     */
    public static function dot($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend.$key.'.'));
            } else {
                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Get all of the given array except for a specified array of items.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function except($array, $keys)
    {
        static::forget($array, $keys);

        return $array;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|int  $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param  array  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public static function first($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($array) ? value($default) : reset($array);
        }

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $key, $value)) {
                return $value;
            }
        }

        return value($default);
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param  array  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public static function last($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($array) ? value($default) : end($array);
        }

        return static::first(array_reverse($array), $callback, $default);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  array  $array
     * @param  int  $depth
     * @return array
     */
    public static function flatten($array, $depth = INF)
    {
        $result = [];

        foreach ($array as $item) {
            $item = $item instanceof Collection ? $item->all() : $item;

            if (is_array($item)) {
                if ($depth === 1) {
                    $result = array_merge($result, $item);
                    continue;
                }

                $result = array_merge($result, static::flatten($item, $depth - 1));
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return void
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (! static::accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }

    /**
     * Check if an item exists in an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string  $key
     * @return bool
     */
    public static function has($array, $key)
    {
        if (! $array) {
            return false;
        }

        if (is_null($key)) {
            return false;
        }

        if (static::exists($array, $key)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     *
     * @param  array  $array
     * @return bool
     */
    public static function isAssoc(array $array)
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param  array  $array
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    public static function pluck($array, $value, $key = null)
    {
        $results = [];

        list($value, $key) = static::explodePluckParameters($value, $key);

        foreach ($array as $item) {
            $itemValue = data_get($item, $value);

            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = data_get($item, $key);

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Explode the "value" and "key" arguments passed to "pluck".
     *
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    protected static function explodePluckParameters($value, $key)
    {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }

    /**
     * Push an item onto the beginning of an array.
     *
     * @param  array  $array
     * @param  mixed  $value
     * @param  mixed  $key
     * @return array
     */
    public static function prepend($array, $value, $key = null)
    {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }

    /**
     * Get a value from the array, and remove it.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function pull(&$array, $key, $default = null)
    {
        $value = static::get($array, $key, $default);

        static::forget($array, $key);

        return $value;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Sort the array using the given callback.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    public static function sort($array, callable $callback)
    {
        return Collection::make($array)->sortBy($callback)->all();
    }

    /**
     * Recursively sort an array by keys and values.
     *
     * @param  array  $array
     * @return array
     */
    public static function sortRecursive($array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = static::sortRecursive($value);
            }
        }

        if (static::isAssoc($array)) {
            ksort($array);
        } else {
            sort($array);
        }

        return $array;
    }

    /**
     * Filter the array using the given callback.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    public static function where($array, callable $callback)
    {
        $filtered = [];

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $key, $value)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * get_col 获取二维数组中指定的列
     *
     * @param  array  $data    必须为二维数组
     * @param  string $keyWord 所要列的键名
     * @param  string $key     列键名
     * @return array
     */
    public static function getCol($data, $keyword, $key = null) {
        if (!is_array($data)) {
            return array();
        }
        $result = array();
        if ($key && is_string($key)) {
            foreach ($data as $value) {
                $result[$value[$key]] = $value[$keyword];
            }
        } else {
            foreach ($data as $value) {
                $result[] = $value[$keyword];
            }
        }
        return $result;
    }

    /**
     * get_property
     * 获取数组中每一个对象的属性
     *
     * @param  array $data 数组的每一项必须为对象
     * @param  string $property 对象的属性名
     * @param  string $key 同级的属性名
     * @return array
     */
    public static function getProperty($data, $property, $key = null) {
        if (!is_array($data)) {
            return false;
        }
        $result = array();
        if ($key && is_string($key)) {
            foreach ($data as $object) {
                $result[$object->$key] = $object->$property;
            }
        } else {
            foreach ($data as $object) {
                $result[] = $object->$property;
            }
        }
        return $result;
    }

    /**
     * rebuild_by_col
     * 根据某个字段把该字段的值当数组的KEY重组数组
     * 例如 $a = array(
     *                 array('uId' => '1', 'data' => 'test'),
     *                 array('uId' => '2', 'data' => 'test2')
     *                )
     * Util_Array::rebuildByCol($a, 'uId');
     * array(
     *       '1' => array('uId' => '1', 'data' => 'test'),
     *       '2' => array('uId' => '2', 'data' => 'test2')
     *      )
     *
     * @param  array $data 二维数组
     * @param  string $keyword 字段名
     * @return array
     */
    public static function rebuildByCol($data, $keyword) {

        // 无数据原样返回
        if (!$data) {
            return $data;
        }

        $result = array();

        foreach ($data as $value) {
            $result[$value[$keyword]] = $value;
        }

        return $result;
    }

    /**
     * rebuild_by_property
     * 根据数组中对象的属性重组数组
     * 用法类似于rebuild_by_col
     *
     * @param  array $data 对象数组
     * @param  string $property 对象的属性名
     * @return array
     */
    public static function rebuildByProperty($data, $property) {

        // 无数据原样返回
        if (!$data) {
            return $data;
        }

        $result = array();

        foreach ($data as $object) {
            $result[$object->$property] = $object;
        }
        return $result;
    }

    /**
     * sort_by_field
     * 对二维数组，按指定的字段值排序
     * 特别适用于MySQL select IN 条件按照指定字段排序
     *
     * 例如 $data = array(
     *                    array('uId' => '9', 'data' => 'test1'),
     *                    array('uId' => '3', 'data' => 'test2'),
     *                    array('uId' => '2', 'data' => 'test3'),
     *                    array('uId' => '5', 'data' => 'test4'),
     *                   )
     *
     * Util_Array::sortByField($data, 'uId', 'ASC', 'NATURAL');
     *
     *
     * return array(
     *              array('uId' => '2', 'data' => 'test3'),
     *              array('uId' => '3', 'data' => 'test2'),
     *              array('uId' => '5', 'data' => 'test4'),
     *              array('uId' => '9', 'data' => 'test1')
     *             )
     *
     * @param  array $data 二维数组数据
     * @param  string $sortField 排序字段名
     * @param  string $sortType ASC, DESC 排序类型，升序降序
     * @param  string $sortFlag REGULAR, NUMERIC, STRING, NATURAL 比较类型，通常比较、数字比较、字符串比较、自然比较，参见php sort函数
     * @return array
     */
    public static function sortByField($data, $sortField, $sortType = 'ASC', $sortFlag = 'REGULAR') {

        if (!$data || !is_array($data)) {
            return false;
        }

        if (!$sortField) {
            return false;
        }

        $sortType = strtoupper($sortType);
        switch ($sortType) {
            case 'DESC':
                break;
            case 'ASC':
            default:
                $sortType = 'ASC';
        }

        $sortFlag = strtoupper($sortFlag);
        switch ($sortFlag) {
            case 'NUMERIC':
                break;
            case 'STRING':
                break;
            case 'NATURAL':
                break;
            case 'REGULAR':
            default:
                $sortFlag = 'REGULAR';
        }

        self::$__sortField = $sort_field;
        self::$__sortType = $sort_type;
        self::$__sortFlag = $sort_flag;

        usort($data, array(new \Illuminate\Support\Arr(), '__sortByField'));

        // 清理
        self::$__sortField = '';
        self::$__sortType = '';
        self::$__sortFlag = '';

        return $data;
    }

    /**
     * __sortByField
     * usort回调，自定义按行的排序方式
     *
     * @param  array $row1 第一条记录
     * @param  array $row2 第二条记录
     * @return integer row1 < row2 返回-1，row1 = row2 返回0，row1 > row2 返回1。降序排列相反
     */
    private static function __sortByField($row1, $row2) {

        $sortField = self::$__sortField;
        $sortType = self::$__sortType;
        $sortFlag = self::$__sortFlag;

        // 默认认为两个数据相等
        $res = 0;

        if ($sortFlag == 'REGULAR') {
            if ($row1[$sortField] < $row2[$sortField]) {
                $res = -1;
            } elseif ($row1[$sortField] > $row2[$sortField]) {
                $res = 1;
            }
        } elseif ($sortFlag == 'NUMERIC') {
            $res = bccomp($row1[$sortField], $row2[$sortField]);
        } elseif ($sortFlag == 'STRING') {
            $res = strcmp($row1[$sortField], $row2[$sortField]);
        } elseif ($sortFlag == 'NATURAL') {
            $res = strnatcmp($row1[$sortField], $row2[$sortField]);
        }

        if ($sortType == 'DESC') {
            $res *= -1;
        }

        return $res;
    }

    /**
     * max_col
     *
     * 例如 $data = array(
     *                    array('uId' => '9', 'data' => 'test1'),
     *                    array('uId' => '3', 'data' => 'test2'),
     *                    array('uId' => '2', 'data' => 'test3'),
     *                    array('uId' => '5', 'data' => 'test4'),
     *                   )
     *
     * Arr::maxCol($data, 'uId'); result = 9;
     *
     * @param  array $data
     * @param  string $key
     * @return
     */
    public static function maxCol($data, $key) {
        if (!$data) {
            return false;
        }

        $keys = self::getCol($data, $key);
        rsort($keys);

        return $keys[0];
    }

}
