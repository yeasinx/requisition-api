<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCcContactRequest;
use App\Http\Requests\UpdateCcContactRequest;
use App\Http\Resources\CcContactResource;
use App\Models\CcContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CcContactController extends Controller
{
    /**
     * List all active contacts (unpaginated, for the CC picker).
     */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', CcContact::class);

        return CcContactResource::collection(CcContact::orderBy('name')->get())->response();
    }

    /**
     * Add a contact to the directory.
     */
    public function store(StoreCcContactRequest $request): JsonResponse
    {
        Gate::authorize('create', CcContact::class);

        $contact = CcContact::create($request->validated());

        return (new CcContactResource($contact))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified contact.
     */
    public function show(CcContact $ccContact): JsonResponse
    {
        Gate::authorize('view', $ccContact);

        return (new CcContactResource($ccContact))->response();
    }

    /**
     * Update the specified contact.
     */
    public function update(UpdateCcContactRequest $request, CcContact $ccContact): JsonResponse
    {
        Gate::authorize('update', $ccContact);

        $ccContact->update($request->validated());

        return (new CcContactResource($ccContact))->response();
    }

    /**
     * Soft-delete a contact (past requisitions keep their CC history).
     */
    public function destroy(CcContact $ccContact): JsonResponse
    {
        Gate::authorize('delete', $ccContact);

        $ccContact->delete();

        return response()->json([
            'message' => 'Contact deleted successfully',
        ]);
    }
}
