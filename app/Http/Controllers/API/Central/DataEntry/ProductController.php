<?php

namespace App\Http\Controllers\API\DataEntry;

use App\Filters\Global\OrderByFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\DataEntry\ProductRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\DataEntry\ProductResource;
use App\Models\Product;
use App\Trait\Global\HasSoftDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use function __;

class ProductController extends Controller implements HasMiddleware
{
    use HasSoftDeleteMethods;

    public function __construct()
    {
        $this->setSoftDeleteModel(Product::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-product'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create-product'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-product'), only: ['update']),
        ];
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Product::query())
            ->through([OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, ProductResource::class));
    }

    /**
     * @param ProductRequest $request
     * @return JsonResponse
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return successResponse(new ProductResource($product), __('api.created_success'));
    }

    /**
     * @param Product $product
     * @return JsonResponse
     */
    public function show(Product $product): JsonResponse
    {
        return successResponse(new ProductResource($product));
    }

    /**
     * @param ProductRequest $request
     * @param Product $product
     * @return JsonResponse
     */
    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return successResponse(new ProductResource($product->refresh()), __('api.updated_success'));
    }
}
