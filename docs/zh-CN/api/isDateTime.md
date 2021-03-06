isDateTime
==========

检查数据是否为合法的日期时间

案例
----

### 检查"2013-01-01 10:00"是否为合法的日期时间

```php
if (wei()->isDateTime('2013-01-01 10:00')) {
    echo 'Yes';
} else {
    echo 'No';
}
```

#### 运行结果

```php
'Yes'
```

### 检查"2013-01-01"是否为符合格式"Ymd"的日期时间

```php
if (wei()->isDateTime('20130101', 'Ymd')) {
    echo 'Yes';
} else {
    echo 'No';
}
```
#### 运行结果

```php
'Yes'
```

调用方式
--------

### 选项

名称              | 类型     | 默认值       | 说明
------------------|----------|--------------|------
format            | string   |Y-m-d H:i:s   | 日期时间格式

### 错误信息

名称                    | 信息
------------------------|------
notStringMessage        | %name%必须是字符串
invalidMessage          | %name%必须是合法的日期时间(当日期无法解析时出现)
formatMessage           | %name%不是合法的日期,格式应该是%format%,例如:%example%
tooEarlyMessage         | %name%必须晚于%after%
tooLateMessage          | %name%必须早于%before%
negativeMessage         | %name%不能是合法的日期

完整的日期格式可以查看PHP官方文档中关于[date](http://php.net/manual/zh/function.date.php)函数的格式说明. 

### 方法

#### isDateTime($input, $format = 'Y-m-d H:i:s')
检查数据是否为合法的日期时间

相关链接
--------

* [验证器概览](../book/validators.md)
* [检查数据是否为合法的日期:isDate](isDate.md)
* [检查数据是否为合法的时间:isTime](isTime.md)