<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Consistent JSON envelope for every API response:
 * { success: bool, message: string|null, data: mixed, meta: object|null, errors?: object }
 */
class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, int $status = 200, ?array $meta = null): JsonResponse
    {
        if ($data instanceof ResourceCollection && $data->resource instanceof LengthAwarePaginator) {
            return self::paginated($data, $message);
        }

        if ($data instanceof JsonResource) {
            $data = $data->resolve(request());
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $status);
    }

    public static function created(mixed $data = null, string $message = 'Created successfully.'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /** Paginated collection: items in data, pagination in meta. */
    public static function paginated(ResourceCollection $collection, ?string $message = null, array $extraMeta = []): JsonResponse
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $collection->resource;

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $collection->resolve(request()),
            'meta' => array_merge([
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ], $extraMeta),
        ]);
    }

    public static function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $body = ['success' => false, 'message' => $message, 'data' => null, 'meta' => null];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}
