<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpSupport;
use Illuminate\Http\Request;

class HelpSupportController extends Controller
{
    public function index()
    {
        $complaints = HelpSupport::with('user')->latest()->paginate(15);
        return view('admin.help_supports.index', compact('complaints'));
    }

    public function show($id)
    {
        $complaint = HelpSupport::with('user')->findOrFail($id);
        return view('admin.help_supports.show', compact('complaint'));
    }

    public function destroy($id)
    {
        $complaint = HelpSupport::findOrFail($id);
        $complaint->delete();
        return redirect()->route('admin.help-supports.index')->with('success', 'Complaint deleted successfully.');
    }
}
