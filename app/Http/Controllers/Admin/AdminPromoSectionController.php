<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\CollectionSearchRequest;
use App\Http\Requests\Promotions\MovePromoSectionRequest;
use App\Http\Requests\Promotions\SavePromoSectionRequest;
use App\Http\Requests\Promotions\SelectPromoCollectionRequest;
use App\Http\Requests\Promotions\TogglePromoSectionRequest;
use App\Models\PromoSection;
use App\Queries\Promotions\ProductCollectionQuery;
use App\Queries\Promotions\PromoSectionQuery;
use App\Services\Promotions\PromoSectionManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminPromoSectionController extends Controller
{
    public function index(CollectionSearchRequest $request, PromoSectionQuery $sections): View
    {
        return view('admin.sections.index', ['sections' => $sections->forAdmin()]);
    }

    public function create(SelectPromoCollectionRequest $request, ProductCollectionQuery $collections): View
    {
        $selected = $request->validated('collection');
        $section = new PromoSection(['layout' => 'grid', 'placement' => 'homepage_main']);
        $section->setRelation('collection', $selected ? $collections->findForPreview((int) $selected) : null);

        return $this->editor($section, $collections);
    }

    public function edit(PromoSection $section, PromoSectionQuery $sections, ProductCollectionQuery $collections): View
    {
        return $this->editor($sections->findForPreview($section->getKey()), $collections);
    }

    private function editor(PromoSection $section, ProductCollectionQuery $query): View
    {
        $choices = $query->recentChoices();
        $selectedId = old('product_collection_id', $section->collection?->getKey());
        if (is_numeric($selectedId) && ! $choices->contains((int) $selectedId)) {
            // The validated selection may be older than the recent dropdown choices.
            if ($selected = $query->findOptional((int) $selectedId)) {
                $choices->push($selected);
            }
        }

        return view('admin.sections.edit', ['section' => $section, 'collections' => $choices]);
    }

    public function store(SavePromoSectionRequest $request, PromoSectionManagementService $service): RedirectResponse
    {
        $section = $service->save($request->validated(), $request->file('promo_image'));

        return to_route('admin.sections.edit', $section)->with('success', __('promotions.section_saved'));
    }

    public function update(SavePromoSectionRequest $request, PromoSection $section, PromoSectionManagementService $service): RedirectResponse
    {
        $service->save($request->validated(), $request->file('promo_image'), $section);

        return to_route('admin.sections.edit', $section)->with('success', __('promotions.section_saved'));
    }

    public function toggle(TogglePromoSectionRequest $request, PromoSection $section, PromoSectionManagementService $service): RedirectResponse
    {
        $service->setActive($section, $request->boolean('is_active'));

        return to_route('admin.sections.index')->with('success', __('promotions.section_saved'));
    }

    public function move(MovePromoSectionRequest $request, PromoSection $section, PromoSectionManagementService $service): RedirectResponse
    {
        $service->move($section, $request->validated('direction'));

        return to_route('admin.sections.index')->with('success', __('promotions.section_order_saved'));
    }

    public function destroy(PromoSection $section, PromoSectionManagementService $service): RedirectResponse
    {
        $service->delete($section);

        return to_route('admin.sections.index')->with('success', __('promotions.section_deleted'));
    }
}
