<?php

namespace App\Http\Controllers\Api\V1\Admin\Crm;

use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Internal notes (polymorphic `notes` table) for leads, appointments and applications. */
trait ManagesNotes
{
    abstract protected function findForNotes(int $id): Model;

    public function addNote(Request $request, int $id): JsonResponse
    {
        $body = $request->validate(['body' => ['required', 'string', 'max:5000']])['body'];
        $note = $this->findForNotes($id)->notes()->create(['user_id' => $request->user()->id, 'body' => $body]);

        return ApiResponse::created($note->load('user:id,name'), 'Note added.');
    }
}
