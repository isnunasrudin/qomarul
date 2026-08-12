<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentCategory;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $data = $request->validate([
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'name' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $file = $request->file('file');

        // MIME asli dari isi berkas (finfo), bukan ekstensi maupun klaim klien
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($file->getContent());

        if (! in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            return back()->withErrors(['file' => 'Berkas harus berupa PDF, JPG, atau PNG.'])->withInput();
        }

        $path = $file->store('documents/'.$employee->id, 'private');

        Document::create([
            'employee_id' => $employee->id,
            'category' => $data['category'],
            'name' => $data['name'] ?? $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $mime,
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', __('common.upload'));
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return Storage::disk('private')->download($document->path, $document->name);
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        Storage::disk('private')->delete($document->path);
        $document->delete();

        return back()->with('success', __('common.deleted'));
    }
}
