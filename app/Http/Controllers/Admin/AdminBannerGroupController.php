<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Banners\SaveBannerGroupRequest;
use App\Models\BannerGroup;
use App\Services\Banners\BannerGroupManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBannerGroupController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = trim($data['q'] ?? '');
        $groups = BannerGroup::query()->with('placements')->withCount('cards')
            ->when($query !== '', fn ($builder) => $builder->where('name', 'like', '%'.$query.'%'))
            ->orderByDesc('banner_group_id')->paginate(20)->withQueryString();

        return view('admin.banners.index', compact('groups', 'query'));
    }

    public function create(): View
    {
        $group = new BannerGroup(['layout' => 'duo', 'is_active' => false]);
        $group->setRelation('cards', collect())->setRelation('placements', collect());

        return view('admin.banners.edit', compact('group'));
    }

    public function edit(BannerGroup $group): View
    {
        $group->load(['cards', 'placements']);

        return view('admin.banners.edit', compact('group'));
    }

    public function store(SaveBannerGroupRequest $request, BannerGroupManagementService $service): RedirectResponse
    {
        $group = $service->save($request->validated());

        return to_route('admin.banner-groups.edit', $group)->with('success', __('banner_groups.saved'));
    }

    public function update(SaveBannerGroupRequest $request, BannerGroup $group, BannerGroupManagementService $service): RedirectResponse
    {
        $service->save($request->validated(), $group);

        return to_route('admin.banner-groups.edit', $group)->with('success', __('banner_groups.saved'));
    }

    public function destroy(BannerGroup $group, BannerGroupManagementService $service): RedirectResponse
    {
        $service->delete($group);

        return to_route('admin.banner-groups.index')->with('success', __('banner_groups.deleted'));
    }
}
