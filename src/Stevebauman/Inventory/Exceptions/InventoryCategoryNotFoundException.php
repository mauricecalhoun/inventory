<?php

namespace Stevebauman\Inventory\Exceptions;

use Illuminate\Support\Facades\App;
use Stevebauman\CoreHelper\Exceptions\AbstractException;

class InventoryCategoryNotFoundException extends AbstractException {

    public function __construct(){
        $this->message = trans('maintenance::errors.not-found', array('resource'=>'Inventory Category'));
        $this->messageType = 'danger';
        $this->redirect = routeBack('maintenance.inventory.categories.index');
    }

}

App::error(function(InventoryNotFoundException $e){
    return $e->response();
});