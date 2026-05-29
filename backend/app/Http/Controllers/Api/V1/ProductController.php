<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * Paginated, searchable list. By default returns active products only;
     * ?include_archived=1 includes archived ones (FR-027).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->integer('per_page', 25), 100);

        $query = Product::query()->search($request->string('search')->toString());

        if (! $request->boolean('include_archived')) {
            $query->active();
        }

        $sort = $request->string('sort', 'name')->toString();
        $sort = in_array($sort, ['name', 'created_at', 'unit_price_cents'], true) ? $sort : 'name';
        $query->orderBy($sort);

        return ProductResource::collection($query->paginate($perPage));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        // tenant_id is auto-stamped by BelongsToTenant; never set here.
        $product = Product::create($request->validated());

        return ProductResource::make($product)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Read. Implicit binding runs through the tenant scope, so another tenant's
     * product is not found and Laravel returns 404 — never 403. No ownership
     * check is written here; isolation is inherited.
     */
    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return ProductResource::make($product);
    }

    /**
     * Archive (FR-027). The row is retained and stays readable; it drops out of
     * the active() picker. This is NOT a delete — there is no destroy endpoint
     * for products, because a product referenced by an invoice must never
     * vanish.
     */
    public function archive(Product $product): ProductResource
    {
        $product->update(['is_active' => false]);

        return ProductResource::make($product);
    }

    public function unarchive(Product $product): ProductResource
    {
        $product->update(['is_active' => true]);

        return ProductResource::make($product);
    }
}
