isOneOf
=======

检查数据是否满足指定规则中的任何一条

案例
----

### 检查"abc"是否为数字且最大长度不超过2

```php
$rules = array(
    'digit' => true,
    'maxLength' => 2
);
if (wei()->isOneOf('abc', $rules)) {
    echo 'Yes';
} else {
    echo 'No';
}
```

#### 运行结果

```php
'No'
```

调用方式
--------

### 选项

名称  | 类型    | 默认值  | 说明
------|---------|---------|------
rules | array   | -       | 验证规则数组,数组的键名是规则名称,数组的值是验证规则的配置选项

### 错误信息

名称                   | 信息
-----------------------|------
atLeastMessage         | %name%至少需要满足以下任何一条规则 | -                                                              |

### 方法

#### isOneOf($input, $rules)
检查数据是否满足指定规则中的任何一条

相关链接
--------

* [验证器概览](../book/validators.md)