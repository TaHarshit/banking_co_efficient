<?php

namespace App\General;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Validate
{

    public static function required($data, $field = array())
    {
        $messages     = ['required' => trans('validation.required')];
        $fieldArr     = array_fill_keys($field, 'required');
        $validator     = Validator::make($data, $fieldArr, $messages);
        return $validator;
    }

    public static function email($data, $field = array())
    {
        $messages     = ['email' => trans('validation.email')];
        $fieldArr     = array_fill_keys($field, 'email');
        $validator     = Validator::make($data, $fieldArr, $messages);
        return $validator;
    }

    public static function different($data, $field1, $field2)
    {
        $rules      = array($field2 => 'different:' . $field1);
        $messages   = ['different' => trans('validation.different')];
        $validator  = Validator::make($data, $rules, $messages);
        return $validator;
    }

    public static function unique($data, $field = array())
    {
        $messages     = ['email' => trans('validation.unique')];
        $fieldArr     = array_fill_keys($field, 'unique:users');
        $validator     = Validator::make($data, $fieldArr, $messages);
        return $validator;
    }

    public static function uniqueUsername($data, $field = array())
    {
        $messages     = ['username' => trans('validation.unique')];
        $fieldArr     = array_fill_keys($field, 'unique:users,username');
        $validator     = Validator::make($data, $fieldArr, $messages);
        return $validator;
    }

    public static function same($data, $field1, $field2)
    {
        $rules         = array($field2 => 'same:' . $field1);
        $messages     = ['same' => trans('validation.same')];
        $validator     = Validator::make($data, $rules, $messages);
        return $validator;
    }

    public static function dateformat($data, $field = array(), $format)
    {
        $messages     = ['email' => trans('validation.date_format')];
        $fieldArr     = array_fill_keys($field, 'date_format:' . $format);
        $validator     = Validator::make($data, $fieldArr, $messages);
        return $validator;
    }

    public static function integer($data, $field = array())
    {
        $messages     = ['integer' => trans('validation.integer')];
        $fieldArr     = array_fill_keys($field, 'integer');
        $validator     = Validator::make($data, $fieldArr, $messages);
        return $validator;
    }

    public static function imageType($data, $field = array())
    {
        $messages = ['mimes' => trans('validation.mimes', ['mimes' => 'jpeg,bmp,png,jpg'])];
        $fieldArr = array_fill_keys($field, 'mimes:' . 'jpeg,bmp,png,jpg');
        $validator = Validator::make($data->all(), $fieldArr, $messages);
        return $validator;
    }

    public static function FileType($data, $field = array())
    {
        $messages = ['mimes' => trans('validation.mimes', ['mimes' => 'jpeg,bmp,png,jpg,pdf,doc,docx'])];
        $fieldArr = array_fill_keys($field, 'mimes:' . 'jpeg,bmp,png,jpg,pdf,doc,docx');
        $validator = Validator::make($data->all(), $fieldArr, $messages);
        return $validator;
    }

    public static function imageSize($data, $field = array())
    {
        $messages = ['max' => trans('validation.image_size', ['size' => config('enum.general.filesize.image')]), 'mimes' => trans('validation.image_mimes', ['mimes' => config('enum.general.filesize.image_mimes')])];
        $fieldArr = array_fill_keys($field, 'mimes:' . config('enum.general.filesize.image_mimes') . '|max:' . config('enum.general.filesize.image'));
        $validator = Validator::make($data, $fieldArr, $messages);
        return $validator;
    }


    /**
     * Throw the failed validation exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    protected function throwValidationException($request, $validator)
    {
        throw new ValidationException($validator, $this->buildFailedValidationResponse(
            $request,
            $this->formatValidationErrors($validator)
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function buildFailedValidationResponse($request, array $errors)
    {
        if (isset(static::$responseBuilder)) {
            return call_user_func(static::$responseBuilder, $request, $errors);
        }
        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    protected function formatValidationErrors($validator)
    {
        if (isset(static::$errorFormatter)) {
            return call_user_func(static::$errorFormatter, $validator);
        }

        return $validator->errors()->getMessages();
    }
}
