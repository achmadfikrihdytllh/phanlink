<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SavedMessageResource;
use App\Services\SavedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedController extends Controller
{
    public function __construct(protected SavedService $savedService)
    {
    }

    /** Filters: collection_id, type (all|messages|files), q. */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'collection_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'in:all,messages,files'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        return response()->json([
            'saved' => SavedMessageResource::collection($this->savedService->list($user, $filters)),
            'collections' => $this->collectionsPayload($user),
            'total' => $this->savedService->total($user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message_id' => ['required', 'integer', 'exists:messages,id'],
            'collection_id' => ['nullable', 'integer'],
        ]);

        $saved = $this->savedService->save($request->user(), $data['message_id'], $data['collection_id'] ?? null);

        return response()->json([
            'message' => 'Pesan disimpan.',
            'saved' => new SavedMessageResource($saved),
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->savedService->remove($request->user(), $id);

        return response()->json(['message' => 'Pesan dihapus dari tersimpan.']);
    }

    public function collections(Request $request): JsonResponse
    {
        return response()->json(['collections' => $this->collectionsPayload($request->user())]);
    }

    public function storeCollection(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:50']]);

        $collection = $this->savedService->createCollection($request->user(), $data['name']);

        return response()->json([
            'message' => 'Koleksi dibuat.',
            'collection' => ['id' => $collection->id, 'name' => $collection->name, 'count' => 0],
        ], 201);
    }

    public function destroyCollection(Request $request, int $id): JsonResponse
    {
        $this->savedService->deleteCollection($request->user(), $id);

        return response()->json(['message' => 'Koleksi dihapus.']);
    }

    private function collectionsPayload($user)
    {
        return $this->savedService->collections($user)->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'count' => $c->saved_messages_count,
        ]);
    }
}
