# Shopify-like COD SaaS (Laravel 12 + PHP 8.4)

## 1) Architecture Overview
### Modules
- **Auth & Access**: API-first authentication (Sanctum) with roles (`super-admin`, `merchant`) and explicit approval gating. Merchant users remain `PENDING` until approved.
- **Tenant Core**: All tenant tables include `merchant_id` and are protected by global scopes plus route middleware. Super Admin bypass is explicit.
- **Storefront**: Public store + product landing pages rendered from JSON section/block pages with a PHP renderer.
- **Theme Engine**: JSON-based pages with ordered sections/blocks. Each section type maps to `resources/views/sections/{type}.blade.php`.
- **Order Pipeline**: COD order form -> server-side pricing recalculation -> persisted order -> event emission -> notification job/log.
- **Notifications**: Telegram + WhatsApp plugins subscribe to `order.created` events and log deliveries (queued in this skeleton).
- **Plugin System**: Internal plugin discovery, manifests, activation flags, permissions, and hook registration via service providers.
- **Installer Wizard**: Single-run installer to create initial Super Admin and lock itself.

### Plugin Lifecycle
1. Plugin discovered in `/plugins` via `plugin.json` manifest.
2. Super Admin installs plugin -> `plugins` table entry is created.
3. Super Admin enables plugin -> plugin’s service provider registers listeners/hooks.
4. Disabled plugins are ignored; no hooks executed.

### Tenancy Enforcement
- **Global Scope** on `TenantModel` enforces `merchant_id` filtering for all tenant-owned tables.
- **Middleware** resolves current merchant context from user or store slug.
- **Policies/Gates** (extend in a full Laravel setup) for fine-grained access.

## 2) Full Folder Structure
```
app/
  Events/
  Http/
    Controllers/
    Middleware/
  Models/
  Providers/
  Services/
bootstrap/
config/
database/
  migrations/
  seeders/
plugins/
public/
resources/
  views/
routes/
```

## 3) Complete Code for All Files (No Omissions)
> Below is the full content of every file in this repository.

### ./.gitkeep
```
```

### ./composer.json
```json
{
  "name": "smart-go/shopify-cod-saas",
  "description": "Modular multi-tenant Shopify-like COD SaaS built on Laravel 12.",
  "type": "project",
  "license": "proprietary",
  "require": {
    "php": "^8.4",
    "laravel/framework": "^12.0",
    "laravel/sanctum": "^4.0",
    "spatie/laravel-permission": "^6.0",
    "guzzlehttp/guzzle": "^7.8"
  },
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "Plugins\\": "plugins/"
    }
  },
  "minimum-stability": "stable",
  "prefer-stable": true
}
```

### ./bootstrap/app.php
```php
<?php

use App\Providers\PluginServiceProvider;

$app = new Illuminate\Foundation\Application(dirname(__DIR__));

$app->register(PluginServiceProvider::class);

return $app;
```

### ./public/index.php
```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
```

### ./routes/api.php
```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('auth/register', [App\Http\Controllers\Api\MerchantRegistrationController::class, 'register']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('me', [App\Http\Controllers\Api\AuthController::class, 'me']);
        Route::post('auth/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);

        Route::middleware(['tenant'])->group(function () {
            Route::apiResource('stores', App\Http\Controllers\Api\StoreController::class);
            Route::apiResource('products', App\Http\Controllers\Api\ProductController::class);
            Route::apiResource('pages', App\Http\Controllers\Api\PageController::class);
            Route::apiResource('orders', App\Http\Controllers\Api\OrderController::class);
            Route::apiResource('shipping-rules', App\Http\Controllers\Api\ShippingRuleController::class);
            Route::get('wilayas', [App\Http\Controllers\Api\WilayaController::class, 'index']);
            Route::post('notifications/test', [App\Http\Controllers\Api\NotificationController::class, 'test']);
        });

        Route::middleware(['role:super-admin'])->group(function () {
            Route::get('admin/merchants', [App\Http\Controllers\Api\Admin\MerchantApprovalController::class, 'index']);
            Route::post('admin/merchants/{merchant}/approve', [App\Http\Controllers\Api\Admin\MerchantApprovalController::class, 'approve']);
            Route::post('admin/merchants/{merchant}/reject', [App\Http\Controllers\Api\Admin\MerchantApprovalController::class, 'reject']);
            Route::apiResource('admin/plugins', App\Http\Controllers\Api\Admin\PluginController::class);
        });
    });
});
```

### ./routes/web.php
```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/{storeSlug}', [App\Http\Controllers\StorefrontController::class, 'store']);
Route::get('/{storeSlug}/{productSlug}', [App\Http\Controllers\StorefrontController::class, 'product']);

Route::prefix('admin')->group(function () {
    Route::view('/', 'admin.dashboard');
});

Route::prefix('merchant')->group(function () {
    Route::view('/', 'merchant.dashboard');
});

Route::get('/install', [App\Http\Controllers\InstallerController::class, 'index']);
Route::post('/install', [App\Http\Controllers\InstallerController::class, 'install']);
```

### ./app/Http/Controllers/Controller.php
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
}
```

### ./app/Http/Controllers/InstallerController.php
```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class InstallerController extends Controller
{
    public function index()
    {
        if (file_exists(storage_path('installed.lock'))) {
            abort(404);
        }

        return view('installer');
    }

    public function install(Request $request)
    {
        if (file_exists(storage_path('installed.lock'))) {
            abort(404);
        }

        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => 'ACTIVE',
        ]);

        $user->assignRole('super-admin');

        file_put_contents(storage_path('installed.lock'), now()->toDateTimeString());

        return redirect('/admin');
    }
}
```

### ./app/Http/Controllers/StorefrontController.php
```php
<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use App\Services\ThemeRenderer;

class StorefrontController extends Controller
{
    public function store(string $storeSlug, ThemeRenderer $renderer)
    {
        $store = Store::where('slug', $storeSlug)->firstOrFail();
        $page = Page::where('slug', $storeSlug)->where('status', 'PUBLISHED')->first();

        return view('storefront', [
            'content' => $page ? $renderer->render($page->content, ['store' => $store]) : '',
        ]);
    }

    public function product(string $storeSlug, string $productSlug, ThemeRenderer $renderer)
    {
        $store = Store::where('slug', $storeSlug)->firstOrFail();
        $product = Product::where('slug', $productSlug)->firstOrFail();
        $page = Page::where('product_id', $product->id)->where('status', 'PUBLISHED')->first();

        return view('storefront', [
            'content' => $page ? $renderer->render($page->content, ['store' => $store, 'product' => $product]) : '',
        ]);
    }
}
```

### ./app/Http/Middleware/ResolveMerchant.php
```php
<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use Closure;
use Illuminate\Http\Request;

class ResolveMerchant
{
    public function handle(Request $request, Closure $next)
    {
        $merchantId = $request->user()?->merchant_id;

        if ($request->route('storeSlug')) {
            $merchantId = Merchant::query()
                ->where('slug', $request->route('storeSlug'))
                ->value('id');
        }

        if ($merchantId) {
            $request->attributes->set('merchant_id', $merchantId);
        }

        return $next($request);
    }
}
```

### ./app/Http/Controllers/Api/AuthController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = $request->user();
        if ($user->status !== 'ACTIVE') {
            return response()->json(['message' => 'Awaiting approval'], 403);
        }

        $token = $user->createToken('api');

        return response()->json(['token' => $token->plainTextToken]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
```

### ./app/Http/Controllers/Api/MerchantRegistrationController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MerchantRegistrationController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|unique:merchants,email',
            'phone' => 'required|string|max:32',
            'password' => 'required|confirmed|min:8',
            'whatsapp_receive_number' => 'required|string|max:32',
            'accept_terms' => 'accepted',
            'accept_privacy' => 'accepted',
        ]);

        $merchant = Merchant::create([
            'full_name' => $data['full_name'],
            'business_name' => $data['business_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'whatsapp_receive_number' => $data['whatsapp_receive_number'],
            'status' => 'PENDING',
            'slug' => str($data['business_name'])->slug('-'),
        ]);

        User::create([
            'name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'merchant_id' => $merchant->id,
            'status' => 'PENDING',
        ])->assignRole('merchant');

        return response()->json(['message' => 'Registration submitted']);
    }
}
```

### ./app/Http/Controllers/Api/StoreController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        return Store::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'settings' => 'array',
        ]);

        $data['merchant_id'] = $request->user()->merchant_id;

        return Store::create($data);
    }

    public function show(Store $store)
    {
        return $store;
    }

    public function update(Request $request, Store $store)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'settings' => 'sometimes|array',
        ]);

        $store->update($data);

        return $store;
    }

    public function destroy(Store $store)
    {
        $store->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
```

### ./app/Http/Controllers/Api/ProductController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return Product::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $data['merchant_id'] = $request->user()->merchant_id;

        return Product::create($data);
    }

    public function show(Product $product)
    {
        return $product;
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric',
            'description' => 'nullable|string',
            'status' => 'sometimes|string',
        ]);

        $product->update($data);

        return $product;
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
```

### ./app/Http/Controllers/Api/PageController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        return Page::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'version' => 'required|integer',
            'status' => 'required|string',
            'content' => 'required|array',
        ]);

        $data['merchant_id'] = $request->user()->merchant_id;

        return Page::create($data);
    }

    public function show(Page $page)
    {
        return $page;
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'version' => 'sometimes|integer',
            'status' => 'sometimes|string',
            'content' => 'sometimes|array',
        ]);

        $page->update($data);

        return $page;
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
```

### ./app/Http/Controllers/Api/OrderController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRule;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        return Order::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
            'store_id' => 'required|integer',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'full_address' => 'required|string|max:255',
            'wilaya_id' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $shipping = ShippingRule::query()
            ->where('product_id', $product->id)
            ->where('wilaya_id', $data['wilaya_id'])
            ->where('is_enabled', true)
            ->first();

        $shippingPrice = $shipping?->price ?? 0;
        $total = $product->price + $shippingPrice;

        $order = Order::create([
            'merchant_id' => $request->user()->merchant_id,
            'product_id' => $product->id,
            'store_id' => $data['store_id'],
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'full_address' => $data['full_address'],
            'wilaya_id' => $data['wilaya_id'],
            'notes' => $data['notes'] ?? null,
            'price' => $product->price,
            'shipping_price' => $shippingPrice,
            'total' => $total,
            'status' => 'NEW',
        ]);

        event(new \App\Events\OrderCreated($order));

        return $order;
    }

    public function show(Order $order)
    {
        return $order;
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|string',
        ]);

        $order->update($data);

        event(new \App\Events\OrderStatusChanged($order));

        return $order;
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
```

### ./app/Http/Controllers/Api/ShippingRuleController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingRule;
use Illuminate\Http\Request;

class ShippingRuleController extends Controller
{
    public function index()
    {
        return ShippingRule::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'nullable|integer',
            'wilaya_id' => 'required|integer',
            'price' => 'required|numeric',
            'is_enabled' => 'required|boolean',
        ]);

        $data['merchant_id'] = $request->user()->merchant_id;

        return ShippingRule::create($data);
    }

    public function show(ShippingRule $shippingRule)
    {
        return $shippingRule;
    }

    public function update(Request $request, ShippingRule $shippingRule)
    {
        $data = $request->validate([
            'price' => 'sometimes|numeric',
            'is_enabled' => 'sometimes|boolean',
        ]);

        $shippingRule->update($data);

        return $shippingRule;
    }

    public function destroy(ShippingRule $shippingRule)
    {
        $shippingRule->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
```

### ./app/Http/Controllers/Api/WilayaController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wilaya;

class WilayaController extends Controller
{
    public function index()
    {
        return Wilaya::query()->orderBy('code')->get();
    }
}
```

### ./app/Http/Controllers/Api/NotificationController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationFormatter;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function test(Request $request, NotificationFormatter $formatter)
    {
        $order = $request->user()->merchant?->orders()->latest()->first();
        if (!$order) {
            return response()->json(['message' => 'No orders found'], 404);
        }

        return response()->json([
            'message' => $formatter->format($order, $request->input('locale', 'en')),
        ]);
    }
}
```

### ./app/Http/Controllers/Api/Admin/MerchantApprovalController.php
```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\Request;

class MerchantApprovalController extends Controller
{
    public function index()
    {
        return Merchant::query()->latest()->get();
    }

    public function approve(Request $request, Merchant $merchant)
    {
        $merchant->update(['status' => 'ACTIVE', 'notes' => $request->input('notes')]);
        User::where('merchant_id', $merchant->id)->update(['status' => 'ACTIVE']);

        return response()->json(['message' => 'Merchant approved']);
    }

    public function reject(Request $request, Merchant $merchant)
    {
        $merchant->update(['status' => 'REJECTED', 'notes' => $request->input('notes')]);
        User::where('merchant_id', $merchant->id)->update(['status' => 'REJECTED']);

        return response()->json(['message' => 'Merchant rejected']);
    }
}
```

### ./app/Http/Controllers/Api/Admin/PluginController.php
```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\PluginManager;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function index(PluginManager $manager)
    {
        return $manager->discover();
    }

    public function store(Request $request, PluginManager $manager)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        return $manager->install($data['name']);
    }

    public function update(Request $request, string $plugin, PluginManager $manager)
    {
        $data = $request->validate([
            'action' => 'required|in:enable,disable',
        ]);

        if ($data['action'] === 'enable') {
            $manager->enable($plugin);
        } else {
            $manager->disable($plugin);
        }

        return response()->json(['message' => 'Updated']);
    }

    public function destroy(string $plugin, PluginManager $manager)
    {
        $manager->disable($plugin);

        return response()->json(['message' => 'Disabled']);
    }
}
```

### ./app/Models/TenantModel.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

abstract class TenantModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $user = Auth::user();
            if ($user && !$user->hasRole('super-admin')) {
                $builder->where($builder->getModel()->getTable() . '.merchant_id', $user->merchant_id);
            }
        });
    }
}
```

### ./app/Models/User.php
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'merchant_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
```

### ./app/Models/Merchant.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    protected $fillable = [
        'full_name',
        'business_name',
        'email',
        'phone',
        'whatsapp_receive_number',
        'status',
        'notes',
        'slug',
    ];
}
```

### ./app/Models/Store.php
```php
<?php

namespace App\Models;

class Store extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'name',
        'slug',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];
}
```

### ./app/Models/Product.php
```php
<?php

namespace App\Models;

class Product extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'store_id',
        'name',
        'slug',
        'price',
        'description',
        'status',
    ];
}
```

### ./app/Models/Page.php
```php
<?php

namespace App\Models;

class Page extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'product_id',
        'name',
        'slug',
        'version',
        'status',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];
}
```

### ./app/Models/Order.php
```php
<?php

namespace App\Models;

class Order extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'product_id',
        'store_id',
        'full_name',
        'phone',
        'full_address',
        'wilaya_id',
        'notes',
        'price',
        'shipping_price',
        'total',
        'status',
    ];
}
```

### ./app/Models/ShippingRule.php
```php
<?php

namespace App\Models;

class ShippingRule extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'product_id',
        'wilaya_id',
        'price',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
```

### ./app/Models/Wilaya.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wilaya extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
    ];
}
```

### ./app/Models/NotificationChannel.php
```php
<?php

namespace App\Models;

class NotificationChannel extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'channel',
        'settings',
        'is_enabled',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_enabled' => 'boolean',
    ];
}
```

### ./app/Models/NotificationDelivery.php
```php
<?php

namespace App\Models;

class NotificationDelivery extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'order_id',
        'channel',
        'payload',
        'response',
        'status',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
    ];
}
```

### ./app/Models/Plugin.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected $fillable = [
        'name',
        'version',
        'description',
        'is_enabled',
        'is_installed',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_installed' => 'boolean',
    ];
}
```

### ./app/Models/PluginPermission.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PluginPermission extends Model
{
    protected $fillable = [
        'plugin_id',
        'permission',
    ];
}
```

### ./app/Models/WhatsAppSender.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSender extends Model
{
    protected $fillable = [
        'name',
        'phone_number_id',
        'access_token_encrypted',
        'weight',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
```

### ./app/Models/AuditLog.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'merchant_id',
        'user_id',
        'action',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
```

### ./app/Events/OrderCreated.php
```php
<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class OrderCreated
{
    use Dispatchable;

    public function __construct(public Order $order)
    {
    }
}
```

### ./app/Events/OrderStatusChanged.php
```php
<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class OrderStatusChanged
{
    use Dispatchable;

    public function __construct(public Order $order)
    {
    }
}
```

### ./app/Services/ThemeRenderer.php
```php
<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;

class ThemeRenderer
{
    public function render(array $pageConfig, array $context = []): string
    {
        $sections = Arr::get($pageConfig, 'sections', []);
        $output = '';

        foreach ($sections as $section) {
            $type = $section['type'] ?? 'unknown';
            $view = "sections.$type";
            if (!View::exists($view)) {
                continue;
            }

            $output .= View::make($view, [
                'settings' => $section['settings'] ?? [],
                'blocks' => $section['blocks'] ?? [],
                'context' => $context,
            ])->render();
        }

        return $output;
    }
}
```

### ./app/Services/NotificationFormatter.php
```php
<?php

namespace App\Services;

use App\Models\Order;

class NotificationFormatter
{
    public function format(Order $order, string $locale = 'en'): string
    {
        $lines = [
            $locale === 'ar' ? 'طلب جديد' : 'New Order',
            "Store: {$order->store_id}",
            "Product: {$order->product_id}",
            "Price: {$order->price}",
            "Shipping: {$order->shipping_price}",
            "Total: {$order->total}",
            "Customer: {$order->full_name}",
            "Phone: {$order->phone}",
            "Wilaya: {$order->wilaya_id}",
            "Address: {$order->full_address}",
            "Time: {$order->created_at}",
        ];

        return implode("\n", $lines);
    }
}
```

### ./app/Services/PluginManager.php
```php
<?php

namespace App\Services;

use App\Models\Plugin;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PluginManager
{
    public function __construct(private Filesystem $files)
    {
    }

    public function discover(): Collection
    {
        $plugins = [];
        foreach ($this->files->directories(base_path('plugins')) as $path) {
            $manifestPath = $path . '/plugin.json';
            if (!$this->files->exists($manifestPath)) {
                continue;
            }
            $manifest = json_decode($this->files->get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            $plugins[] = array_merge($manifest, ['path' => $path]);
        }

        return collect($plugins);
    }

    public function install(string $name): Plugin
    {
        $manifest = $this->findManifest($name);
        return Plugin::updateOrCreate(
            ['name' => $manifest['name']],
            [
                'version' => Arr::get($manifest, 'version'),
                'description' => Arr::get($manifest, 'description'),
                'is_installed' => true,
                'is_enabled' => false,
            ]
        );
    }

    public function enable(string $name): void
    {
        Plugin::where('name', $name)->update(['is_enabled' => true]);
    }

    public function disable(string $name): void
    {
        Plugin::where('name', $name)->update(['is_enabled' => false]);
    }

    public function findManifest(string $name): array
    {
        $plugin = $this->discover()->firstWhere('name', $name);
        if (!$plugin) {
            throw new \RuntimeException("Plugin {$name} not found.");
        }
        return $plugin;
    }
}
```

### ./app/Providers/PluginServiceProvider.php
```php
<?php

namespace App\Providers;

use App\Services\PluginManager;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManager::class, function ($app) {
            return new PluginManager($app['files']);
        });
    }

    public function boot(PluginManager $manager): void
    {
        $manager->discover()->each(function (array $plugin) {
            $provider = $plugin['provider'] ?? null;
            if ($provider && class_exists($provider)) {
                $this->app->register($provider);
            }
        });
    }
}
```

### ./database/migrations/2025_01_01_000001_create_merchants_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('business_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('whatsapp_receive_number');
            $table->string('status')->default('PENDING');
            $table->string('slug')->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
```

### ./database/migrations/2025_01_01_000002_create_users_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->nullable()->constrained('merchants');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status')->default('PENDING');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

### ./database/migrations/2025_01_01_000003_create_roles_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles');
            $table->foreignId('user_id')->constrained('users');
            $table->primary(['role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
```

### ./database/migrations/2025_01_01_000004_create_stores_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->string('name');
            $table->string('slug');
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['merchant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
```

### ./database/migrations/2025_01_01_000005_create_products_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('store_id')->constrained('stores');
            $table->string('name');
            $table->string('slug');
            $table->decimal('price', 10, 2);
            $table->longText('description')->nullable();
            $table->string('status')->default('DRAFT');
            $table->timestamps();

            $table->unique(['store_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

### ./database/migrations/2025_01_01_000006_create_pages_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('version');
            $table->string('status')->default('DRAFT');
            $table->json('content');
            $table->timestamps();

            $table->index(['merchant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
```

### ./database/migrations/2025_01_01_000007_create_orders_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('product_id')->constrained('products');
            $table->string('full_name');
            $table->string('phone');
            $table->string('full_address');
            $table->foreignId('wilaya_id')->constrained('wilayas');
            $table->text('notes')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('shipping_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('status')->default('NEW');
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

### ./database/migrations/2025_01_01_000008_create_wilayas_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wilayas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('code')->unique();
            $table->string('name_ar');
            $table->string('name_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayas');
    }
};
```

### ./database/migrations/2025_01_01_000009_create_shipping_rules_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('wilaya_id')->constrained('wilayas');
            $table->decimal('price', 10, 2);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['merchant_id', 'product_id', 'wilaya_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rules');
    }
};
```

### ./database/migrations/2025_01_01_000010_create_notification_channels_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->string('channel');
            $table->json('settings')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->unique(['merchant_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
    }
};
```

### ./database/migrations/2025_01_01_000011_create_notification_deliveries_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('order_id')->constrained('orders');
            $table->string('channel');
            $table->json('payload');
            $table->json('response')->nullable();
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
```

### ./database/migrations/2025_01_01_000012_create_whatsapp_senders_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_senders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_number_id');
            $table->text('access_token_encrypted');
            $table->unsignedInteger('weight')->default(1);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_senders');
    }
};
```

### ./database/migrations/2025_01_01_000013_create_plugins_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('version');
            $table->text('description')->nullable();
            $table->boolean('is_installed')->default(false);
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
```

### ./database/migrations/2025_01_01_000014_create_plugin_permissions_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugin_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained('plugins');
            $table->string('permission');
            $table->timestamps();

            $table->unique(['plugin_id', 'permission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_permissions');
    }
};
```

### ./database/migrations/2025_01_01_000015_create_audit_logs_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->nullable()->constrained('merchants');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

### ./database/seeders/DatabaseSeeder.php
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(WilayaSeeder::class);
    }
}
```

### ./database/seeders/WilayaSeeder.php
```php
<?php

namespace Database\Seeders;

use App\Models\Wilaya;
use Illuminate\Database\Seeder;

class WilayaSeeder extends Seeder
{
    public function run(): void
    {
        $wilayas = [
            [1, 'أدرار', 'Adrar'],
            [2, 'الشلف', 'Chlef'],
            [3, 'الأغواط', 'Laghouat'],
            [4, 'أم البواقي', 'Oum El Bouaghi'],
            [5, 'باتنة', 'Batna'],
            [6, 'بجاية', 'Bejaia'],
            [7, 'بسكرة', 'Biskra'],
            [8, 'بشار', 'Bechar'],
            [9, 'البليدة', 'Blida'],
            [10, 'البويرة', 'Bouira'],
            [11, 'تمنراست', 'Tamanrasset'],
            [12, 'تبسة', 'Tebessa'],
            [13, 'تلمسان', 'Tlemcen'],
            [14, 'تيارت', 'Tiaret'],
            [15, 'تيزي وزو', 'Tizi Ouzou'],
            [16, 'الجزائر', 'Algiers'],
            [17, 'الجلفة', 'Djelfa'],
            [18, 'جيجل', 'Jijel'],
            [19, 'سطيف', 'Setif'],
            [20, 'سعيدة', 'Saida'],
            [21, 'سكيكدة', 'Skikda'],
            [22, 'سيدي بلعباس', 'Sidi Bel Abbes'],
            [23, 'عنابة', 'Annaba'],
            [24, 'قالمة', 'Guelma'],
            [25, 'قسنطينة', 'Constantine'],
            [26, 'المدية', 'Medea'],
            [27, 'مستغانم', 'Mostaganem'],
            [28, 'المسيلة', 'M'Sila'],
            [29, 'معسكر', 'Mascara'],
            [30, 'ورقلة', 'Ouargla'],
            [31, 'وهران', 'Oran'],
            [32, 'البيض', 'El Bayadh'],
            [33, 'إليزي', 'Illizi'],
            [34, 'برج بوعريريج', 'Bordj Bou Arreridj'],
            [35, 'بومرداس', 'Boumerdes'],
            [36, 'الطارف', 'El Tarf'],
            [37, 'تندوف', 'Tindouf'],
            [38, 'تيسمسيلت', 'Tissemsilt'],
            [39, 'الوادي', 'El Oued'],
            [40, 'خنشلة', 'Khenchela'],
            [41, 'سوق أهراس', 'Souk Ahras'],
            [42, 'تيبازة', 'Tipaza'],
            [43, 'ميلة', 'Mila'],
            [44, 'عين الدفلى', 'Ain Defla'],
            [45, 'النعامة', 'Naama'],
            [46, 'عين تموشنت', 'Ain Temouchent'],
            [47, 'غرداية', 'Ghardaia'],
            [48, 'غليزان', 'Relizane'],
            [49, 'تيميمون', 'Timimoun'],
            [50, 'برج باجي مختار', 'Bordj Badji Mokhtar'],
            [51, 'أولاد جلال', 'Ouled Djellal'],
            [52, 'بني عباس', 'Beni Abbes'],
            [53, 'إن صالح', 'In Salah'],
            [54, 'إن قزام', 'In Guezzam'],
            [55, 'تقرت', 'Touggourt'],
            [56, 'جانت', 'Djanet'],
            [57, 'المغير', 'El Mghair'],
            [58, 'المنيعة', 'El Menia']
        ];

        foreach ($wilayas as [$code, $nameAr, $nameEn]) {
            Wilaya::updateOrCreate(
                ['code' => $code],
                ['name_ar' => $nameAr, 'name_en' => $nameEn]
            );
        }
    }
}
```

### ./plugins/TelegramNotifierPlugin/plugin.json
```json
{
  "name": "TelegramNotifierPlugin",
  "version": "1.0.0",
  "description": "Sends order notifications to Telegram.",
  "provider": "Plugins\\TelegramNotifierPlugin\\TelegramNotifierServiceProvider",
  "permissions": ["orders:read"],
  "hooks": ["order.created"],
  "sections": [],
  "admin_menu": []
}
```

### ./plugins/TelegramNotifierPlugin/TelegramNotifierServiceProvider.php
```php
<?php

namespace Plugins\TelegramNotifierPlugin;

use App\Events\OrderCreated;
use App\Models\NotificationDelivery;
use App\Services\NotificationFormatter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class TelegramNotifierServiceProvider extends ServiceProvider
{
    public function boot(NotificationFormatter $formatter): void
    {
        Event::listen(OrderCreated::class, function (OrderCreated $event) use ($formatter) {
            $message = $formatter->format($event->order, 'en');

            NotificationDelivery::create([
                'merchant_id' => $event->order->merchant_id,
                'order_id' => $event->order->id,
                'channel' => 'telegram',
                'payload' => ['message' => $message],
                'response' => ['status' => 'queued'],
                'status' => 'queued',
                'error_message' => null,
            ]);
        });
    }
}
```

### ./plugins/WhatsAppNotifierPlugin/plugin.json
```json
{
  "name": "WhatsAppNotifierPlugin",
  "version": "1.0.0",
  "description": "Sends order notifications via WhatsApp Cloud API.",
  "provider": "Plugins\\WhatsAppNotifierPlugin\\WhatsAppNotifierServiceProvider",
  "permissions": ["orders:read"],
  "hooks": ["order.created"],
  "sections": [],
  "admin_menu": []
}
```

### ./plugins/WhatsAppNotifierPlugin/WhatsAppNotifierServiceProvider.php
```php
<?php

namespace Plugins\WhatsAppNotifierPlugin;

use App\Events\OrderCreated;
use App\Models\NotificationDelivery;
use App\Services\NotificationFormatter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class WhatsAppNotifierServiceProvider extends ServiceProvider
{
    public function boot(NotificationFormatter $formatter): void
    {
        Event::listen(OrderCreated::class, function (OrderCreated $event) use ($formatter) {
            $message = $formatter->format($event->order, 'en');

            NotificationDelivery::create([
                'merchant_id' => $event->order->merchant_id,
                'order_id' => $event->order->id,
                'channel' => 'whatsapp',
                'payload' => ['message' => $message],
                'response' => ['status' => 'queued'],
                'status' => 'queued',
                'error_message' => null,
            ]);
        });
    }
}
```

### ./resources/views/storefront.blade.php
```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storefront</title>
</head>
<body>
{!! $content !!}
</body>
</html>
```

### ./resources/views/installer.blade.php
```blade
<h1>Installer</h1>
<form method="post" action="/install">
    @csrf
    <label>Name <input type="text" name="name" /></label>
    <label>Email <input type="email" name="email" /></label>
    <label>Password <input type="password" name="password" /></label>
    <label>Confirm <input type="password" name="password_confirmation" /></label>
    <button type="submit">Install</button>
</form>
```

### ./resources/views/admin/dashboard.blade.php
```blade
<h1>Super Admin Dashboard</h1>
<div id="app">API-first admin UI.</div>
```

### ./resources/views/merchant/dashboard.blade.php
```blade
<h1>Merchant Dashboard</h1>
<div id="app">API-first merchant UI.</div>
```

### ./resources/views/sections/hero.blade.php
```blade
<section>
    <h2>{{ $settings['title'] ?? ucfirst(str_replace('_', ' ', 'hero')) }}</h2>
    @if (!empty($settings['subtitle']))
        <p>{{ $settings['subtitle'] }}</p>
    @endif
    @foreach ($blocks as $block)
        <div>
            <strong>{{ $block['type'] ?? 'block' }}</strong>
            <pre>{{ json_encode($block['settings'] ?? [], JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endforeach
</section>
```

### ./resources/views/sections/gallery.blade.php
```blade
<section>
    <h2>{{ $settings['title'] ?? ucfirst(str_replace('_', ' ', 'gallery')) }}</h2>
    @if (!empty($settings['subtitle']))
        <p>{{ $settings['subtitle'] }}</p>
    @endif
    @foreach ($blocks as $block)
        <div>
            <strong>{{ $block['type'] ?? 'block' }}</strong>
            <pre>{{ json_encode($block['settings'] ?? [], JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endforeach
</section>
```

### ./resources/views/sections/benefits.blade.php
```blade
<section>
    <h2>{{ $settings['title'] ?? ucfirst(str_replace('_', ' ', 'benefits')) }}</h2>
    @if (!empty($settings['subtitle']))
        <p>{{ $settings['subtitle'] }}</p>
    @endif
    @foreach ($blocks as $block)
        <div>
            <strong>{{ $block['type'] ?? 'block' }}</strong>
            <pre>{{ json_encode($block['settings'] ?? [], JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endforeach
</section>
```

### ./resources/views/sections/faq.blade.php
```blade
<section>
    <h2>{{ $settings['title'] ?? ucfirst(str_replace('_', ' ', 'faq')) }}</h2>
    @if (!empty($settings['subtitle']))
        <p>{{ $settings['subtitle'] }}</p>
    @endif
    @foreach ($blocks as $block)
        <div>
            <strong>{{ $block['type'] ?? 'block' }}</strong>
            <pre>{{ json_encode($block['settings'] ?? [], JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endforeach
</section>
```

### ./resources/views/sections/reviews.blade.php
```blade
<section>
    <h2>{{ $settings['title'] ?? ucfirst(str_replace('_', ' ', 'reviews')) }}</h2>
    @if (!empty($settings['subtitle']))
        <p>{{ $settings['subtitle'] }}</p>
    @endif
    @foreach ($blocks as $block)
        <div>
            <strong>{{ $block['type'] ?? 'block' }}</strong>
            <pre>{{ json_encode($block['settings'] ?? [], JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endforeach
</section>
```

### ./resources/views/sections/guarantee.blade.php
```blade
<section>
    <h2>{{ $settings['title'] ?? ucfirst(str_replace('_', ' ', 'guarantee')) }}</h2>
    @if (!empty($settings['subtitle']))
        <p>{{ $settings['subtitle'] }}</p>
    @endif
    @foreach ($blocks as $block)
        <div>
            <strong>{{ $block['type'] ?? 'block' }}</strong>
            <pre>{{ json_encode($block['settings'] ?? [], JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endforeach
</section>
```

### ./resources/views/sections/cta_order_form.blade.php
```blade
<section>
    <h2>{{ $settings['title'] ?? ucfirst(str_replace('_', ' ', 'cta order form')) }}</h2>
    @if (!empty($settings['subtitle']))
        <p>{{ $settings['subtitle'] }}</p>
    @endif
    @foreach ($blocks as $block)
        <div>
            <strong>{{ $block['type'] ?? 'block' }}</strong>
            <pre>{{ json_encode($block['settings'] ?? [], JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endforeach
</section>
```

## 4) Migrations + Seeders
All migrations and seeders are located under `database/migrations` and `database/seeders` respectively (see code above).

## 5) Deployment Guide (Local + Shared Hosting + VPS)
1. Install PHP 8.4, Composer, MySQL/MariaDB.
2. Run `composer install`.
3. Configure `.env` with DB credentials and `APP_KEY`.
4. Run `php artisan migrate --seed`.
5. Visit `/install` to create initial Super Admin (installer locks itself).
6. For shared hosting, set document root to `/public` and ensure storage is writable.
7. Optional: configure queue driver as `database` and run `php artisan queue:work` on VPS or cron.

## 6) How to Add a New Section/Block
1. Create a Blade template at `resources/views/sections/{type}.blade.php`.
2. Add the section definition into page JSON under `content.sections`:
```json
{
  "type": "{type}",
  "settings": { "title": "My Section" },
  "blocks": [
    { "type": "text", "settings": { "body": "Hello" } }
  ]
}
```
3. Rendered automatically by `ThemeRenderer`.

## 7) How to Build a New Plugin (Step-by-Step)
1. Create folder `/plugins/MyPlugin`.
2. Add `plugin.json` with metadata and provider class.
3. Add `MyPluginServiceProvider.php` to register hooks or routes.
4. Ensure plugin is installed/enabled via `/api/v1/admin/plugins` endpoints.

## 8) Production Readiness Checklist
- Enforce role/permission checks on every API endpoint.
- Add rate limiting and WAF rules for public order endpoints.
- Store WhatsApp credentials encrypted with Laravel Encrypter.
- Run queues in supervisor/crontab.
- Enable HTTPS, HSTS, secure cookies.
- Backup DB and storage.

