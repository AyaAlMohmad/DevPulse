<?php

namespace App\Http\Requests;

use App\Http\Middleware\SetLocale;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(SetLocale::SUPPORTED)],
            'redirect' => ['required', 'string', Rule::in(['home', 'login', 'register', 'dashboard', 'monitors.show'])],
            'monitor_id' => [Rule::requiredIf($this->input('redirect') === 'monitors.show'), 'nullable', 'integer', 'min:1'],
            //
        ];
    }
}
