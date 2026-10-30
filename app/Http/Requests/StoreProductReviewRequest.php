<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductReviewRequest extends FormRequest
{
    protected $errorBag = 'review';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'review_form' => ['required', 'string', 'max:100', 'regex:/^review-form-[a-z0-9-]+$/'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        $url = parent::getRedirectUrl();
        $form = $this->input('review_form');

        return is_string($form) && preg_match('/^review-form-[a-z0-9-]{1,80}$/', $form)
            ? preg_replace('/#.*$/', '', $url).'#'.$form
            : $url;
    }
}
