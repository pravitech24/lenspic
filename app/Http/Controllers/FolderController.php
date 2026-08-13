<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FolderController extends Controller
{
    public function index(Group $group)
    {
        $this->checkAccess($group);
        $query = $group->folders()->withCount('photos')->with('coverPhoto');
        if (!$group->hasFullAccess(Auth::user())) $query->where('highlighted',true);
        $folders = $query->get();

        return Inertia::render('Groups/Folders', ['group'=>$group->only('id','name'),'canManage'=>$group->isAdmin(Auth::user()),'folders'=>$folders->map(fn($f)=>['id'=>$f->id,'name'=>$f->name,'description'=>$f->description,'color'=>$f->color,'highlighted'=>$f->highlighted,'display_order'=>$f->display_order,'photos_count'=>$f->photos_count])]);
    }

    public function show(Group $group, Folder $folder)
    {
        $this->checkAccess($group);
        abort_unless($folder->group_id === $group->id, 404);
        if (!$group->hasFullAccess(Auth::user())) abort_unless($folder->highlighted,403,'Partial Access only permits the Highlights folder.');

        $photos = $folder->photos()->with('uploader')->latest()->paginate(30);

        return view('folders.show', compact('group', 'folder', 'photos'));
    }

    public function store(Request $request, Group $group)
    {
        $this->checkAccess($group);
        $this->checkWriteAccess($group);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'max:30'],
            'highlighted' => ['nullable', 'boolean'],
        ]);

        $validated['name'] = trim($validated['name']);
        $validated['group_id'] = $group->id;
        $validated['display_order'] = $validated['display_order'] ?? 0;
        $validated['highlighted'] = $request->boolean('highlighted');

        if ($validated['name'] === '') {
            return back()->with('error', 'Folder name is required.');
        }

        $exists = $group->folders()->whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])->exists();
        if ($exists) {
            return back()->with('error', 'Folder already exists.');
        }

        $folder = $group->folders()->create($validated);

        return redirect()->route('folders.index', $group)->with('success', 'Folder created successfully.');
    }

    public function update(Request $request, Group $group, Folder $folder)
    {
        $this->checkAccess($group);
        $this->checkWriteAccess($group);
        abort_unless($folder->group_id === $group->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'max:30'],
            'highlighted' => ['nullable', 'boolean'],
        ]);

        $validated['name'] = trim($validated['name']);
        if ($validated['name'] === '') {
            return back()->with('error', 'Folder name is required.');
        }

        $exists = $group->folders()->where('id', '!=', $folder->id)->whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])->exists();
        if ($exists) {
            return back()->with('error', 'Folder already exists.');
        }

        $folder->update($validated + ['highlighted' => $request->boolean('highlighted')]);

        return back()->with('success', 'Folder updated successfully.');
    }

    public function destroy(Group $group, Folder $folder)
    {
        $this->checkAccess($group);
        $this->checkWriteAccess($group);
        abort_unless($folder->group_id === $group->id, 404);

        if ($folder->photos()->exists()) {
            return back()->with('error', 'Cannot delete a folder containing photos.');
        }

        $folder->delete();

        return back()->with('success', 'Folder deleted successfully.');
    }

    public function transferPhotos(Request $request, Group $group, Folder $folder)
    {
        $this->checkAccess($group);
        $this->checkWriteAccess($group);
        abort_unless($folder->group_id === $group->id, 404);

        $validated = $request->validate([
            'photo_ids' => ['required', 'array', 'min:1'],
            'photo_ids.*' => ['integer', 'exists:photos,id'],
        ]);

        $photos = $group->photos()->whereIn('id', $validated['photo_ids'])->get();

        foreach ($photos as $photo) {
            $photo->update(['folder_id' => $folder->id]);
        }

        return back()->with('success', count($photos) . ' photo(s) transferred to ' . $folder->name);
    }

    public function setCoverPhoto(Request $request, Group $group, Folder $folder)
    {
        $this->checkAccess($group);
        $this->checkWriteAccess($group);
        abort_unless($folder->group_id === $group->id, 404);

        $validated = $request->validate(['photo_id' => ['required', 'integer', 'exists:photos,id']]);
        $photo = $group->photos()->findOrFail($validated['photo_id']);

        $folder->update(['cover_photo_id' => $photo->id]);

        return back()->with('success', 'Folder cover updated.');
    }

    private function checkAccess(Group $group): void
    {
        \Illuminate\Support\Facades\Gate::authorize("view", $group);
    }

    private function checkWriteAccess(Group $group): void
    {
        \Illuminate\Support\Facades\Gate::authorize("create", [\App\Models\Folder::class, $group]);
    }
}
