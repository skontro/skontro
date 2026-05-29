<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\SequenceGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    public function __construct(private readonly SequenceGenerator $sequences) {}

    /**
     * Paginated, searchable list (FR-020). The query is tenant-scoped by the
     * global scope; this only narrows within the current tenant.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->integer('per_page', 25), 100);

        $query = Customer::query()->search($request->string('search')->toString());

        if ($request->boolean('include_deleted')) {
            $query->withTrashed();
        }

        $sort = $request->string('sort', 'created_at')->toString();
        $sort = in_array($sort, ['created_at', 'contact_name', 'number'], true) ? $sort : 'created_at';
        $query->orderBy($sort);

        return CustomerResource::collection($query->paginate($perPage));
    }

    /**
     * Create (FR-016). The number is issued atomically; the tenant is stamped
     * automatically by the BelongsToTenant trait, so it is never set here.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('currentTenant');

        $customer = new Customer($request->validated());
        $customer->number = $this->sequences->next($tenant, DocumentType::Customer);
        $customer->save();

        return CustomerResource::make($customer)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Read (FR-017). Implicit route-model binding runs the lookup through the
     * tenant global scope, so a customer belonging to another tenant is simply
     * not found and Laravel returns 404 — NOT 403. No ownership check is
     * written here; isolation is inherited, and 404 (rather than 403) avoids
     * leaking the existence of another tenant's record.
     */
    public function show(Customer $customer): CustomerResource
    {
        return CustomerResource::make($customer);
    }

    /**
     * Update (FR-018). The number is immutable — UpdateCustomerRequest does not
     * accept it, and BelongsToTenant forbids changing tenant_id.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update($request->validated());

        return CustomerResource::make($customer);
    }

    /**
     * Soft-delete (FR-019). The row is retained so existing invoices remain
     * readable; the customer is excluded from default lists.
     */
    public function destroy(Customer $customer): Response
    {
        $customer->delete();

        return response()->noContent();
    }

    /**
     * Restore a soft-deleted customer (FR-019). Bound explicitly with
     * trashed() because the default scope excludes soft-deleted rows.
     */
    public function restore(string $uuid): CustomerResource
    {
        $customer = Customer::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $customer->restore();

        return CustomerResource::make($customer);
    }
}
