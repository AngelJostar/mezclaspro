<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MixtureMessagingService;
use Illuminate\Http\Request;

class MixtureMessageController extends Controller
{
    public function __construct(private MixtureMessagingService $messaging) {}

    private function response(array $data, int $status = 200)
    {
        return response()->json($data, $status)->header('Cache-Control', 'no-store, private');
    }

    public function summary(Request $request)
    {
        $data = $request->validate(['targets' => 'required|array|min:1|max:50',
            'targets.*' => ['required', 'string', 'max:48', 'regex:/^(nutricionales|oncologicos|antibioticos):[1-9][0-9]{0,15}$/']]);
        return $this->response(['summaries' => $this->messaging->summaries($request->user(),
            $this->messaging->targets($request->user(), $data['targets']))]);
    }

    public function show(Request $request, string $kind, int $target)
    {
        $data = $request->validate(['before_id' => 'nullable|integer|min:1|prohibits:after_id', 'after_id' => 'nullable|integer|min:0']);
        $record = $this->messaging->resolve($request->user(), $kind, $target);
        $query = $this->messaging->messages($record);
        if (!empty($data['after_id'])) $query->where('id', '>', $data['after_id']);
        if (!empty($data['before_id'])) $query->where('id', '<', $data['before_id']);
        $messages = !empty($data['after_id']) ? $query->orderBy('id')->limit(100)->get() : $query->orderByDesc('id')->limit(50)->get()->reverse()->values();
        $oldest = $messages->first()?->id;
        return $this->response([
            'target' => ['kind' => $kind, 'id' => $target, 'hospital' => $record['hospital'], 'patient' => $record['patient']],
            'side' => $this->messaging->side($request->user()),
            'can_send' => $this->messaging->side($request->user()) === 'hospital' || $this->messaging->messages($record)->where('sender_side', 'hospital')->exists(),
            'messages' => $messages->map(fn ($message) => $this->messaging->serialize($message))->all(),
            'has_older' => $oldest && $this->messaging->messages($record)->where('id', '<', $oldest)->exists(),
        ]);
    }

    public function store(Request $request, string $kind, int $target)
    {
        $request->merge(['body' => is_string($request->input('body')) ? trim($request->input('body')) : $request->input('body')]);
        $data = $request->validate(['body' => 'required|string|max:4000', 'client_token' => 'required|uuid']);
        $record = $this->messaging->resolve($request->user(), $kind, $target);
        $message = $this->messaging->send($request->user(), $record, $data['body'], $data['client_token']);
        return $this->response(['message' => $this->messaging->serialize($message)], $message->wasRecentlyCreated ? 201 : 200);
    }

    public function read(Request $request, string $kind, int $target)
    {
        $data = $request->validate(['through_id' => 'required|integer|min:1']);
        $this->messaging->markRead($request->user(), $this->messaging->resolve($request->user(), $kind, $target), $data['through_id']);
        return $this->response(['ok' => true]);
    }
}
