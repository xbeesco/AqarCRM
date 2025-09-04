<?php

namespace App\Traits;

use Illuminate\Support\Facades\Hash;

trait AutoGeneratesCredentials
{
    public static function bootAutoGeneratesCredentials()
    {
        static::creating(function ($model) {
            if (!$model->email && $model->phone) {
                $model->email = $model->phone . '@aqarcrm.com';
            }
            
            if (!$model->password && $model->phone) {
                $model->password = Hash::make($model->phone);
            }
        });
    }
}