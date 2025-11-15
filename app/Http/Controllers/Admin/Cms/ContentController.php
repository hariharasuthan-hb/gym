<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\StoreContentRequest;
use App\Http\Requests\Admin\Cms\UpdateContentRequest;
use App\Repositories\Interfaces\CmsContentRepositoryInterface;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    protected CmsContentRepositoryInterface $repository;
    protected ImageUploadService $imageService;

    public function __construct(
        CmsContentRepositoryInterface $repository,
        ImageUploadService $imageService
    ) {
        $this->repository = $repository;
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->get('search'),
            'type' => $request->get('type'),
            'is_active' => $request->get('is_active'),
        ];

        $sort = [
            $request->get('sort_by', 'order') => $request->get('sort_dir', 'asc')
        ];

        $perPage = $request->get('per_page', 15);

        $contents = $this->repository->all($filters, $sort, $perPage);

        return view('admin.cms.content.index', compact('contents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.cms.content.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->upload(
                $request->file('image'),
                'cms/content'
            );
        }

        // Handle background image upload
        if ($request->hasFile('background_image')) {
            $data['background_image'] = $this->imageService->upload(
                $request->file('background_image'),
                'cms/content'
            );
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $this->repository->create($data);

        return redirect()->route('admin.cms.content.index')
            ->with('success', 'Content created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): View
    {
        $content = $this->repository->findOrFail($id);
        return view('admin.cms.content.show', compact('content'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $content = $this->repository->findOrFail($id);
        return view('admin.cms.content.edit', compact('content'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContentRequest $request, int $id): RedirectResponse
    {
        $content = $this->repository->findOrFail($id);
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->upload(
                $request->file('image'),
                'cms/content',
                $content->image
            );
        }

        // Handle background image upload
        if ($request->hasFile('background_image')) {
            $data['background_image'] = $this->imageService->upload(
                $request->file('background_image'),
                'cms/content',
                $content->background_image
            );
        }

        $data['updated_by'] = auth()->id();

        $this->repository->update($id, $data);

        return redirect()->route('admin.cms.content.index')
            ->with('success', 'Content updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        $content = $this->repository->findOrFail($id);

        // Delete images if exist
        if ($content->image) {
            $this->imageService->delete($content->image);
        }
        if ($content->background_image) {
            $this->imageService->delete($content->background_image);
        }

        $this->repository->delete($id);

        return redirect()->route('admin.cms.content.index')
            ->with('success', 'Content deleted successfully.');
    }
}
