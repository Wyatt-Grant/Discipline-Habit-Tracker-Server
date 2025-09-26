<?php

namespace App\Http\Controllers;

use App\Http\Requests\RuleRequest;
use App\Models\Rule;
use Exception;
use Illuminate\Http\Request;

class RuleController extends Controller
{
    function all(Request $request) {
        $rules = $request
            ->user()
            ->dynamics()
            ->first()
            ->rules()
            ->get();

        return json_encode([
            'rules' => $rules,
        ]);
    }

    function create(RuleRequest $request) {
        try {
            Rule::create([
                'dynamic_id' => $request->user()->dynamics()->first()->id,
                'description' => $request->description
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function update(RuleRequest $request, Rule $rule) {
        try {
            $rule->update([
                'description' => $request->description
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function delete(Request $request, Rule $rule) {
        try {
            $rule->delete();
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }
}
