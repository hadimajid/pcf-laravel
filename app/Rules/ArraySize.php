<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ArraySize implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $attr;
    public function __construct($attr)
    {
        $this->attr=$attr;
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
        return sizeof($this->attr)==sizeof($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Please provide quantity for all products.';
    }
}
