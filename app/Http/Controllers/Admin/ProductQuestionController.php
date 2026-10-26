<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductQuestion;
use Illuminate\Http\Request;

class ProductQuestionController extends Controller
{
    public function index(Request $request)
    {
        $sortOrder = $request->input('sort', 'desc');
        $query = ProductQuestion::with(['product', 'user']);
        
        if ($sortOrder === 'asc') {
            $query->oldest();
        } else {
            $query->latest();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('product', function($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $questions = $query->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return view('admin.product-questions.partials.table', compact('questions'))->render();
        }
            
        return view('admin.product-questions.index', compact('questions'));
    }

    public function update(Request $request, ProductQuestion $question)
    {
        $request->validate([
            'status' => 'required|in:pending,answered,rejected,private',
            'customer_name' => 'nullable|string|max:255',
            'question' => 'required|string',
            'answer' => 'nullable|string'
        ]);

        $question->status = $request->status;
        $question->question = $request->question;
        
        if ($request->has('customer_name')) {
            $question->customer_name = $request->customer_name;
        }

        if ($request->has('answer')) {
            $question->answer = $request->answer;
        }

        $question->save();

        return redirect()->back()->with('success', 'Question updated successfully.');
    }
}
