<form class="language-switcher" method="POST" action="{{ route('language.update') }}">
    @csrf
    <label class="visually-hidden" for="language-choice">{{ __('Pilih bahasa') }}</label>
    <select id="language-choice" name="locale" aria-label="{{ __('Pilih bahasa') }}" onchange="this.form.submit()">
        <option value="id" @selected(app()->getLocale() === 'id')>{{ __('Indonesia') }}</option>
        <option value="su" @selected(app()->getLocale() === 'su')>{{ __('Sunda') }}</option>
        <option value="en" @selected(app()->getLocale() === 'en')>{{ __('Inggris') }}</option>
    </select>
</form>
