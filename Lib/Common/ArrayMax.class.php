<?php
class ArrayMax{
    private $temp ;
    public  $value ;
    public  $spec;
    
    public function __construct($value = null){
        $this->value = $value;
    }
    
    public function arrayMax(){
        $this->spec = $this->value[0];
        foreach ($this->value as $key=>$value){
            if($value <= $this->spec){
                unset($this->value[$value]);
            }else{
                $this->spec = $value;
            }
        }
        return $this->spec;
    }
}