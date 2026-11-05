<?php

namespace App\Http\Controllers\Api\V1\Admin\Crm;

use App\Enums\ContactStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\TemplateMailer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'status' => ['nullable', Rule::enum(ContactStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $filtered = fn () => ContactMessage::query()->when($params['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w
            ->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('subject', 'like', "%{$s}%")->orWhere('message', 'like', "%{$s}%")));

        $page = $filtered()->when($params['status'] ?? null, fn ($q, $s) => $q->where('status', $s), fn ($q) => $q->where('status', '!=', ContactStatus::Archived->value))
            ->latest('id')->paginate($params['per_page'] ?? 20, ['id', 'name', 'email', 'phone', 'inquiry_type', 'subject', 'message', 'status', 'created_at']);

        return ApiResponse::success($page->items(), meta: [
            'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(),
            'total' => $page->total(), 'from' => $page->firstItem(), 'to' => $page->lastItem(),
            'counts' => $filtered()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /** Opening a new message marks it as read. */
    public function show(int $id): JsonResponse
    {
        $message = ContactMessage::query()->with(['lead:id,name,status', 'destination:id,name'])->findOrFail($id);
        if ($message->status === ContactStatus::New) {
            $message->update(['status' => ContactStatus::Read, 'read_at' => now()]);
        }

        return ApiResponse::success($message);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $message = ContactMessage::query()->findOrFail($id);
        $message->update($request->validate(['status' => ['required', Rule::enum(ContactStatus::class)]]));

        return ApiResponse::success($message, 'Message updated.');
    }

    public function reply(Request $request, int $id, TemplateMailer $mailer): JsonResponse
    {
        $message = ContactMessage::query()->findOrFail($id);
        $reply = $request->validate(['reply' => ['required', 'string', 'min:2', 'max:10000']])['reply'];

        $mailer->send('contact_reply', $message->email, ['name' => $message->name, 'reply' => $reply, 'original_message' => $message->message]);
        $message->update(['status' => ContactStatus::Replied, 'reply' => $reply, 'replied_at' => now(), 'replied_by' => $request->user()->id]);

        return ApiResponse::success($message, 'Reply sent.');
    }

    public function destroy(int $id): JsonResponse
    {
        ContactMessage::query()->findOrFail($id)->delete();

        return ApiResponse::success(null, 'Message deleted.');
    }
}
