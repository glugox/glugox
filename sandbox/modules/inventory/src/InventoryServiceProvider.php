<?php

namespace Glugox\Inventory;

use Glugox\Module\BaseModuleServiceProvider;
use Illuminate\Support\Facades\Route;

class InventoryServiceProvider extends BaseModuleServiceProvider
{
    public function registerRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(function () {
                Route::get('inventory/products', static fn () => response()->json([]));
            });
    }
}
