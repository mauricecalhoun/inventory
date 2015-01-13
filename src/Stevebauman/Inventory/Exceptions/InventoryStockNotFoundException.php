<?php

namespace Stevebauman\Inventory\Exceptions;

use Illuminate\Support\Facades\App;
use Stevebauman\CoreHelper\Exceptions\AbstractException;

class InventoryStockNotFoundException extends AbstractException {
    
    public function __construct(){
        $this->message = trans('maintenance::errors.not-found', array('resource'=>'Inventory Stock'));
        $this->messageType = 'danger';
        $this->redirect = routeBack('maintenance.inventory.show', $this->getRouteParameter('inventory'));
    }
    
}

App::error(function(InventoryStockNotFoundException $e, $code, $fromConsole){
    return $e->response();
});