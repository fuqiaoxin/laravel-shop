<?php
/**
 * Created by PhpStorm.
 * User: fay
 * Date: 2018/7/19
 * Time: 上午10:10
 */
function route_class() {
    return str_replace('.','-', Route::currentRouteName());
}