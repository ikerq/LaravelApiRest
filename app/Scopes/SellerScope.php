<?php
/**
 * Created by PhpStorm.
 * User: iquiros
 * Date: 15-03-2018
 * Time: 12:45
 */

namespace App\Scopes;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SellerScope implements Scope
{
    public function apply(Builder $builder, Model $model) {
        $builder->has('products');
    }
}