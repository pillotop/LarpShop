@php
    $existing = ! empty($values['id']) ? $group->cards->firstWhere('banner_card_id', $values['id']) : null;
    $imageUrl = $existing?->image_path ? Storage::disk($existing->image_disk)->url($existing->image_path) : '';
@endphp
<details class="banner-editor-card border rounded-2 p-3 mb-3" data-editor-card data-image-url="{{ $imageUrl }}" open>
    <summary class="fw-semibold">{{ __('banner_groups.card') }} <span data-card-number>{{ is_numeric($index) ? $index + 1 : '' }}</span></summary>
    <div class="d-flex flex-wrap justify-content-end gap-2 my-3">
        @foreach (['up', 'down', 'remove'] as $action)
            <button type="button" class="btn btn-sm btn-outline theme-inverse" data-card-action="{{ $action }}">{{ __('banner_groups.'.$action) }}</button>
        @endforeach
    </div>
    @if (! empty($values['id']))
        <input type="hidden" name="cards[{{ $index }}][id]" value="{{ $values['id'] }}">
    @endif
    <input type="hidden" name="cards[{{ $index }}][sort_order]" value="{{ is_numeric($index) ? $index : 0 }}" data-card-order>
    <div class="row g-3">
        <div class="col-12 md:col-6">
            <label for="card-{{ $index }}-image" class="form-label d-block">{{ __('banner_groups.image') }}</label>
            <input
                id="card-{{ $index }}-image"
                name="cards[{{ $index }}][image]"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="form-control"
                data-card-image
                @required(! $existing)
            >
            <p class="small fg-3 mt-2">{{ __('banner_groups.image_hint') }}</p>
        </div>
        <div class="col-12 md:col-6 d-flex align-items-start gap-2 pt-3">
            <input type="hidden" name="cards[{{ $index }}][is_active]" value="0">
            <input
                id="card-{{ $index }}-active"
                name="cards[{{ $index }}][is_active]"
                type="checkbox"
                value="1"
                @checked($values['is_active'] ?? true)
                class="form-check-input"
                data-card-active
            >
            <label for="card-{{ $index }}-active">{{ __('banner_groups.card_active') }}</label>
        </div>
        @foreach (['presentation' => config('banners.presentations'), 'text_tone' => config('banners.text_tones')] as $field => $options)
            <div class="col-12 md:col-6">
                <label for="card-{{ $index }}-{{ $field }}" class="form-label d-block">{{ __('banner_groups.fields.'.$field) }}</label>
                <select id="card-{{ $index }}-{{ $field }}" name="cards[{{ $index }}][{{ $field }}]" class="form-control" data-card-field="{{ $field }}">
                    @foreach ($options as $option)
                        <option value="{{ $option }}" @selected(($values[$field] ?? ($field === 'presentation' ? 'top_copy' : 'dark')) === $option)>
                            {{ __('banner_groups.'.($field === 'presentation' ? 'presentations' : 'tones').'.'.$option) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endforeach
        @foreach (['focal_x', 'focal_y'] as $field)
            <div class="col-12 md:col-6">
                <label for="card-{{ $index }}-{{ $field }}" class="form-label d-block">{{ __('banner_groups.fields.'.$field) }}</label>
                <input id="card-{{ $index }}-{{ $field }}" name="cards[{{ $index }}][{{ $field }}]" type="range" min="0" max="100" value="{{ $values[$field] ?? 50 }}" class="w-100" data-card-field="{{ $field }}">
            </div>
        @endforeach
        <div class="col-12">
            <label for="card-{{ $index }}-link_url" class="form-label d-block">{{ __('banner_groups.fields.link_url') }}</label>
            <input id="card-{{ $index }}-link_url" name="cards[{{ $index }}][link_url]" value="{{ $values['link_url'] ?? '/products' }}" maxlength="2048" class="form-control" required>
        </div>
        @foreach (['en', 'vi'] as $language)
            <fieldset class="col-12 md:col-6">
                <legend class="h6 mt-3">{{ __('banner_groups.content_'.$language) }}</legend>
                @foreach (\App\Models\BannerCard::TEXT_FIELDS as $field)
                    @php
                        $fieldName = $language === 'en' ? $field : 'translations][vi]['.$field;
                        $content = $language === 'en' ? ($values[$field] ?? '') : ($values['translations']['vi'][$field] ?? '');
                    @endphp
                    <div class="mb-3">
                        <label for="card-{{ $index }}-{{ $language }}-{{ $field }}" class="form-label d-block">{{ __('banner_groups.fields.'.$field) }}</label>
                        <input
                            id="card-{{ $index }}-{{ $language }}-{{ $field }}"
                            name="cards[{{ $index }}][{{ $fieldName }}]"
                            value="{{ $content }}"
                            maxlength="255"
                            class="form-control"
                            data-copy-field="{{ $field }}"
                            data-copy-locale="{{ $language }}"
                            @required($language === 'en' && in_array($field, ['title', 'alt_text']))
                        >
                    </div>
                @endforeach
            </fieldset>
        @endforeach
    </div>
</details>
