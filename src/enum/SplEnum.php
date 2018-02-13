<?php
/**
 * spl enum 自实现类
 */
class SplEnum {
    
    const __default = null ;
    var $__default = null;
    /**
     * 构造器
     * @param mixed $initial_value 初始值
     * @param boolean $strict 是否严格匹配
     * @throws \Exception
     */
    public function __construct( $initial_value=null, $strict=TRUE ) {
        if( $initial_value!==null && !in_array($initial_value, self::getConstList(),$strict) ){
            throw new UnexpectedValueException('Value not a const in enum '.static::class);
        }
        $this->__default = $initial_value;
    }
    /**
     * 获得已定义的所有常量
     * @param boolean $include_default 是否返回默认值
     * @return type
     */
    public static function getConstList( $include_default =FALSE ){
        $reflect = new \ReflectionClass( static::class );
        $result = $reflect->getConstants();
        if( !$include_default ){
            unset($result['__default']);
        }
        return $result;
    }
    /**
     * 获得键的值
     * @param string $key
     * @return type
     * @throws Exception
     */
    private function getValue( $key ) {
        $constants = self::getConstList();
        if( !isset($constants[$key]) ){
            throw new UnexpectedValueException( 'Key not a const in enum '.static::class );
        }
        return $constants[$key];
    }
    /**
     * 判断键是否合法
     * @param string $key
     * @return boolean
     */
    public function isValidName( $key ) {
        $constants = self::getConstList();
        if( !isset($constants[$key]) ){
            return FALSE;
        }
        return TRUE;
    }
    /**
     * 判断值是否合法
     * @param mixed $value
     * @return boolean
     */
    public function isValidValue( $value ) {
        $constants = self::getConstList();
        if( !in_array( $value, $constants, true ) ){
            return FALSE;
        }
        return TRUE;
    }
    
    /**
     * 使用 echo 方法调用
     * @return string
     */
    public function __toString(){
        return $this->__default;
    }
    /**
     * 使用 var_dump/print_r 方法调用
     * @return array()
    */ 
    public function  __debugInfo(){
        return array(
            '__default'=>  $this->__default
        );
    }
    
    public static function __callStatic($name, $arguments) {
        echo 'xxxx';
    }
}

class UnexpectedValueException extends Exception{
    
}
