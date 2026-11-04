<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Contacts ("confidants"). The {userId} in URLs is the contact's *user* id. */
class ContactController extends Controller
{
    public function __construct(protected ContactService $contactService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'contacts' => ContactResource::collection($this->contactService->list($request->user())),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'nickname' => ['nullable', 'string', 'max:50'],
        ]);

        $contact = $this->contactService->add($request->user(), $data['user_id'], $data['nickname'] ?? null);

        return response()->json([
            'message' => 'Kontak ditambahkan.',
            'contact' => new ContactResource($contact),
        ], 201);
    }

    /** Profile page: the contact plus shared groups, shared files and the last exchange. */
    public function show(Request $request, int $userId): JsonResponse
    {
        $contact = $this->contactService->find($request->user(), $userId);
        $detail = $this->contactService->detail($request->user(), $contact);

        return response()->json([
            'contact' => new ContactResource($contact),
            'conversation_id' => $detail['conversation_id'],
            'groups_in_common' => ConversationResource::collection($detail['groups_in_common']),
            'shared_files' => AttachmentResource::collection($detail['shared_files']),
            'last_exchange' => MessageResource::collection($detail['last_exchange']),
        ]);
    }

    public function update(Request $request, int $userId): JsonResponse
    {
        $data = $request->validate([
            'nickname' => ['sometimes', 'nullable', 'string', 'max:50'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_muted' => ['sometimes', 'boolean'],
            'is_blocked' => ['sometimes', 'boolean'],
        ]);

        $contact = $this->contactService->find($request->user(), $userId);

        return response()->json([
            'contact' => new ContactResource($this->contactService->update($contact, $data)),
        ]);
    }

    public function destroy(Request $request, int $userId): JsonResponse
    {
        $this->contactService->remove($this->contactService->find($request->user(), $userId));

        return response()->json(['message' => 'Kontak dihapus.']);
    }
}
