<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class Unique implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public  $tableName;
    public  $columnName;
    public  $id;
    public function __construct($tableName, $columnName,$id=null)
    {
        $this->tableName=$tableName;
        $this->columnName=$columnName;
        $this->id=$id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $check=false;
        if($this->id==null){
                    $result=DB::table($this->tableName)->where($this->columnName,$value)->first();
                    if(!$result){
                        $check=true;
                    }
        }else{
                    $result=DB::table($this->tableName)->where($this->columnName,$value)->where('id',$this->id)->first();
                    if($result){
                        $check=true;
                    }
        }
        return $check;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ':attribute should be unique.';
    }
}
